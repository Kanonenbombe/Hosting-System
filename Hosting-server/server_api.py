from fastapi.middleware.cors import CORSMiddleware
from fastapi import FastAPI, Request, HTTPException, Depends, Security
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from pydantic import BaseModel
import uvicorn
import subprocess
import os
from mysql.connector import connect, Error
import time
import requests
import psutil
import shutil
from hashlib import sha256

from Game_Server_Service_router import app as Game_Server_Service_router_app
from Config import Config



app = FastAPI()
security = HTTPBearer()
app.include_router(Game_Server_Service_router_app)




## Setzt alle variablen auf die config einstellungen ###
vboxmanage_path = Config.vboxmanage_path
destination_path = Config.vms_path
main_api_url = Config.main_api_url
port = Config.port
serverart = Config.serverart
ALLOWED_IP = Config.ALLOWED_IP
db_config = Config.db_config


app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Erlaubt allen Ursprüngen
    allow_credentials=True,
    allow_methods=["*"],  # Erlaubt alle Methoden
    allow_headers=["*"],  # Erlaubt alle Header
)




# Versucht, eine Verbindung zur Datenbank herzustellen
try:
    db_connection = connect(**db_config)
    print("Api: Datenbankverbindung erfolgreich hergestellt")
except Error as e:
    print(f"Api: Fehler beim Verbinden zur Datenbank: {e}")
    exit(1)  # Beendet das Programm, wenn keine Verbindung möglich ist





def get_public_ip_address():
    try:
        response = requests.get('https://api.ipify.org')
        return response.text
    except requests.RequestException as e:
        raise ValueError(f"Error fetching public IP address: {e}")


public_ip_address = get_public_ip_address()
print(public_ip_address)

async def verify_ip(request: Request):
    client_host = request.client.host
    if client_host != ALLOWED_IP:
        raise HTTPException(status_code=403 ,detail="Zugriff verweigert")





def get_user_by_token(token: str):
    try:
        with db_connection.cursor(dictionary=True) as cursor:
            cursor.execute("SELECT `ID` FROM `users` WHERE `token` = %s", (token,))
            user = cursor.fetchone()
            if user is None:
                raise HTTPException(status_code=401, detail="Invalid token")
            return user['ID']  # Return just the user ID
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Database error: {e}")





def verify_vm_ownership(user_id: int, vm_name: str):
    try:
        with db_connection.cursor(dictionary=True) as cursor:
            cursor.execute("SELECT `ID`, `name`, `ownerID` FROM `vms` WHERE `name` = %s", (vm_name,))
            vm = cursor.fetchone()
            ownerid = int(vm['ownerID'])

            if vm is None or ownerid != user_id:
                print(vm['ownerID'])
                print(user_id)
                print(vm_name)
                raise HTTPException(status_code=403, detail="Vserver gehört nicht zum Benutzer oder existiert nicht")
            return vm
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Database error: {e}")




def get_ram():
    # Gibt die Gesamtmenge des RAM in Megabytes zurück
    total_memory = psutil.virtual_memory().total // (1024 * 1024)
    return total_memory


# Funktion, um die Anzahl der CPU-Kerne zu bekommen
def get_cpu_count():
    # Gibt die Anzahl der CPU-Kerne zurück
    return psutil.cpu_count(logical=False)

# Funktion, um den gesamten Speicherplatz in GB zu bekommen
def get_storage():
    total, used, free = shutil.disk_usage("/")
    total_gb = total // (1024 * 1024 * 1024)
    return total_gb

def get_available_storage():
    total, used, free = shutil.disk_usage("/")
    return int(free / (1024 * 1024 * 1024))

def register_on_main_api():
    server_details = {
        "ram": get_ram(),
        "cpu": get_cpu_count(),
        "storage": get_available_storage(),
        "port": port,
        "art": serverart
    }

    while True:
        try:
            # Der API-Aufruf
            response = requests.post(f"{main_api_url}/register-server/", json=server_details)

            # Überprüfen der Antwort
            if response.status_code == 200:
                print("Registrierung erfolgreich:", response.json())
                break  # Beenden der Schleife, wenn die Registrierung erfolgreich war
            else:
                print("Fehler bei der Registrierung:", response.text)
                time.sleep(10)  # Warte 10 Sekunden vor dem nächsten Versuch

        except requests.exceptions.RequestException as e:
            print("Fehler bei der Verbindung zur main API")
            time.sleep(10)  # Warte 10 Sekunden vor dem nächsten Versuch

register_on_main_api()









# Erstellen Sie eine Pydantic-Modellklasse, um die Anforderungsdaten zu validieren
class VMCreateRequest(BaseModel):
    template_name: str
    ram: int
    cpus: int
    storage: int
    token: str
    server_id: int






def generate_vm_name():
    try:
        with db_connection.cursor(dictionary=True) as cursor:
            # Wählen Sie den höchsten Namen aus, der mit "Server" beginnt und konvertieren Sie die Zahl, die danach folgt
            cursor.execute("SELECT MAX(CAST(SUBSTRING(name, 7) AS UNSIGNED)) as max_number FROM `vms` WHERE `name` LIKE 'Server%'")
            result = cursor.fetchone()
            max_number = result['max_number'] if result['max_number'] else 10000000
            new_vm_name = f"Server{max_number + 1}"
            return new_vm_name
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Database error: {e}")




@app.get("/ping-server/")
def ping():
    pingpong = "Online"
    return pingpong


@app.post("/create-vm/", dependencies=[Depends(verify_ip)])
def create_vm(vm_create_request: VMCreateRequest):
    user_id = get_user_by_token(vm_create_request.token)
    new_vm_name = generate_vm_name()

    try:
        public_ip_address = get_public_ip_address()
        print(public_ip_address)


    except subprocess.CalledProcessError as e:
        raise HTTPException(status_code=500, detail=f"Failed to get VM IP address: {str(e)}")

    with db_connection.cursor() as cursor:
        cursor.execute(
            "INSERT INTO vms (name, ownerID, IP, ram, cpu, storage, RootPw, serverid, disable_reason, instalstatus) VALUES (%s, %s, %s, %s, %s, %s, %s, %s, %s, %s)",
            (new_vm_name, user_id, public_ip_address, vm_create_request.ram,
             vm_create_request.cpus, vm_create_request.storage, "root", vm_create_request.server_id, "", 1)
        )
        db_connection.commit()
    try:
        cpus= vm_create_request.cpus
        template_name = vm_create_request.template_name
        ram = vm_create_request.ram * 1000
        storage = vm_create_request.storage
        if not os.path.exists(destination_path):
            os.makedirs(destination_path)

            with db_connection.cursor() as cursor:
                cursor.execute(
                    "UPDATE vms SET instalstatus = %s WHERE name = %s",
                    (2, new_vm_name)  # Setzen Sie instalstatus auf 0
                )
                db_connection.commit()

            # Klonvorgang
        subprocess.run([
            vboxmanage_path,
            'clonevm',
            template_name,
            '--name', new_vm_name,
            '--register',
            '--mode', 'machine',
            '--basefolder', destination_path,
            '--options', 'keepallmacs',
        ], check=True)

        # VM anpassen
        full_path_new_vm = os.path.join(destination_path, new_vm_name, new_vm_name + '.vbox')
        subprocess.run([vboxmanage_path, 'modifyvm', new_vm_name, '--memory', str(ram)], check=True)
        subprocess.run([vboxmanage_path, 'modifyvm', new_vm_name, '--cpus', str(cpus)], check=True)

        with db_connection.cursor() as cursor:
            cursor.execute(
                "UPDATE vms SET instalstatus = %s WHERE name = %s",
                (3, new_vm_name)  # Setzen Sie instalstatus auf 0
            )
            db_connection.commit()

        # Beispiel für die Änderung der Festplattengröße (muss auf eine bestehende VDI-Datei angewendet werden)
        # hier müssen Sie den Pfad zur VDI-Datei der geklonten VM entsprechend anpassen
        disk_path = os.path.join(full_path_new_vm, new_vm_name + '.vdi')
        if os.path.exists(disk_path):
            subprocess.run([vboxmanage_path, 'modifymedium', disk_path, '--resize', str(storage)], check=True)

        with db_connection.cursor() as cursor:
            cursor.execute(
                "UPDATE vms SET instalstatus = %s WHERE name = %s",
                (4, new_vm_name)  # Setzen Sie instalstatus auf 0
            )
            db_connection.commit()

        subprocess.run([vboxmanage_path, 'startvm', new_vm_name, '--type', 'headless'], check=True)

        time.sleep(1)

        with db_connection.cursor() as cursor:
            cursor.execute(
                "UPDATE vms SET instalstatus = %s WHERE name = %s",
                (0, new_vm_name)  # Setzen Sie instalstatus auf 0
            )
            db_connection.commit()






        return {"status": "success", "message": f"Vserver erfolgreich installiert"}
    except subprocess.CalledProcessError as e:
        raise HTTPException(status_code=500, detail=str(e))
    except Error as e:
        db_connection.rollback()
        raise HTTPException(status_code=500, detail=f"Database error: {e}")








class VMDeleteRequest(BaseModel):
    vm_name: str
    token: str

@app.post("/delete-vm/", dependencies=[Depends(verify_ip)])
def delete_vm(vm_delete_request: VMDeleteRequest):
    user_id = get_user_by_token(vm_delete_request.token)
    vm = verify_vm_ownership(user_id, vm_delete_request.vm_name)
    try:
        # Löschvorgang der VM mit VirtualBox
        subprocess.run([
            vboxmanage_path,
            'unregistervm',
            vm_delete_request.vm_name,
            '--delete'
        ], check=True)

        # VM aus der Datenbank löschen
        with db_connection.cursor() as cursor:
            cursor.execute("DELETE FROM vms WHERE name = %s", (vm_delete_request.vm_name,))
            db_connection.commit()

        return {"status": "success", "message": f"Vserver {vm_delete_request.vm_name} deleted successfully."}
    except subprocess.CalledProcessError as e:
        raise HTTPException(status_code=500, detail=str(e))
    except Error as e:
        db_connection.rollback()  # Rollback im Fehlerfall
        raise HTTPException(status_code=500, detail=f"Database error: {e}")








class VMControlRequest(BaseModel):
    vm_name: str
    token: str


@app.post("/start-vm/", dependencies=[Depends(verify_ip)])
def start_vm(vm_control_request: VMControlRequest):
    user_id = get_user_by_token(vm_control_request.token)
    vm = verify_vm_ownership(user_id, vm_control_request.vm_name)

    try:
        with db_connection.cursor(dictionary=True) as cursor:  # Hier wird `dictionary=True` hinzugefügt
            cursor.execute(
                "SELECT disable_reason FROM vms WHERE name = %s",
                (vm_control_request.vm_name,)
            )
            vm_info = cursor.fetchone()
            if vm_info is None:
                raise HTTPException(status_code=404, detail="VM not found")

            if vm_info['disable_reason']:  # Jetzt sollte dieser Teil korrekt funktionieren
                return {"status": "error","message": f"Vserver {vm_control_request.vm_name} is disabled. Reason: {vm_info['disable_reason']}"}

        subprocess.run([
            vboxmanage_path,
            'startvm',
            vm_control_request.vm_name,
            '--type', 'headless'
        ], check=True)
        return {"status": "success", "message": f"Vserver {vm_control_request.vm_name} started successfully."}
    except subprocess.CalledProcessError as e:
        raise HTTPException(status_code=500, detail=str(e))
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Database error: {e}")


@app.post("/stop-vm/", dependencies=[Depends(verify_ip)])
def stop_vm(vm_control_request: VMControlRequest):
    user_id = get_user_by_token(vm_control_request.token)
    vm = verify_vm_ownership(user_id, vm_control_request.vm_name)
    try:
        with db_connection.cursor(dictionary=True) as cursor:  # Hier wird `dictionary=True` hinzugefügt
            cursor.execute(
                "SELECT disable_reason FROM vms WHERE name = %s",
                (vm_control_request.vm_name,)
            )
            vm_info = cursor.fetchone()
            if vm_info is None:
                raise HTTPException(status_code=404, detail="Vserver not found")

            if vm_info['disable_reason']:  # Jetzt sollte dieser Teil korrekt funktionieren
                return {"status": "error","message": f"Vserver {vm_control_request.vm_name} is disabled. Reason: {vm_info['disable_reason']}"}




        subprocess.run([
            vboxmanage_path,
            'controlvm',
            vm_control_request.vm_name,
            'poweroff'
        ], check=True)
        return {"status": "success", "message": f"Vserver {vm_control_request.vm_name} stopped successfully."}
    except subprocess.CalledProcessError as e:
        raise HTTPException(status_code=500, detail=str(e))

@app.post("/restart-vm/", dependencies=[Depends(verify_ip)])
def restart_vm(vm_control_request: VMControlRequest):
    user_id = get_user_by_token(vm_control_request.token)
    vm = verify_vm_ownership(user_id, vm_control_request.vm_name)
    try:
        with db_connection.cursor(dictionary=True) as cursor:  # Hier wird `dictionary=True` hinzugefügt
            cursor.execute(
                "SELECT disable_reason FROM vms WHERE name = %s",
                (vm_control_request.vm_name,)
            )
            vm_info = cursor.fetchone()
            if vm_info is None:
                raise HTTPException(status_code=404, detail="VM not found")

            if vm_info['disable_reason']:  # Jetzt sollte dieser Teil korrekt funktionieren
                return {"status": "error","message": f"Vserver {vm_control_request.vm_name} is disabled. Reason: {vm_info['disable_reason']}"}


        # Zuerst die VM stoppen
        subprocess.run([
            vboxmanage_path,
            'controlvm',
            vm_control_request.vm_name,
            'reset'
        ], check=True)
        return {"status": "success", "message": f"Vserver {vm_control_request.vm_name} restarted successfully."}
    except subprocess.CalledProcessError as e:
        raise HTTPException(status_code=500, detail=str(e))





@app.post("/disable-vm/", dependencies=[Depends(verify_ip)])
def stop_vm(vm_control_request: VMControlRequest):
    user_id = get_user_by_token(vm_control_request.token)
    vm = verify_vm_ownership(user_id, vm_control_request.vm_name)
    try:
        # VM deaktivieren
        subprocess.run([
            vboxmanage_path,
            'controlvm',
            vm_control_request.vm_name,
            'poweroff'
        ], check=True)

        # Disable-Grund in der Datenbank aktualisieren
        with db_connection.cursor() as cursor:
            disable_reason = "Disabled from admin!"
            cursor.execute(
                "UPDATE vms SET disable_reason = %s WHERE name = %s",
                (disable_reason, vm_control_request.vm_name)
            )
            db_connection.commit()

        return {"status": "success", "message": f"VM {vm_control_request.vm_name} disabled successfully."}
    except subprocess.CalledProcessError as e:
        db_connection.rollback()
        raise HTTPException(status_code=500, detail=str(e))
    except Error as e:
        db_connection.rollback()
        raise HTTPException(status_code=500, detail=f"Database error: {e}")






if __name__ == "__main__":
    uvicorn.run(app, host="0.0.0.0", port=port)
