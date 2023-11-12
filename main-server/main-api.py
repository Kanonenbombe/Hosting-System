from fastapi import FastAPI, HTTPException, Request
from fastapi.middleware.cors import CORSMiddleware
from pydantic import BaseModel
from mysql.connector import connect, Error
from requests import post
import requests
import asyncio
import time
from rich.console import Console
from rich.table import Table

# ANSI Escape Codes für einige Farben
RED = "\033[31m"   # Rote Farbe
GREEN = "\033[32m" # Grüne Farbe
YELLOW = "\033[33m" # Gelbe Farbe
RESET = "\033[0m"  # Zurücksetzen der Farbe auf Standard





app = FastAPI()



app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],  # Erlaubt allen Ursprüngen
    allow_credentials=True,
    allow_methods=["*"],  # Erlaubt alle Methoden
    allow_headers=["*"],  # Erlaubt alle Header
)

# Datenbankkonfiguration
db_config = {
    "host": "127.0.0.1",
    "user": "main-api",
    "password": "jfo[cNXuG-*KmSjW",
    "database": "panel"
}

# Versucht, eine Verbindung zur Datenbank herzustellen
try:
    db_connection = connect(**db_config)
    print("Datenbankverbindung erfolgreich hergestellt")
except Error as e:
    print(f"Fehler beim Verbinden zur Datenbank: {e}")
    exit(1)  # Beendet das Programm, wenn keine Verbindung möglich ist








async def is_server_reachable(ip, port):
    url = f"http://{ip}:{port}/ping-server/"
    try:
        start_time = time.time()
        response = requests.get(url)
        end_time = time.time()

        response_time_ms = (end_time - start_time) * 1000
        return response.status_code == 200, response_time_ms
    except requests.exceptions.ConnectionError:
        return False, None



async def ping_all_servers():
    console = Console()
    while True:
        await asyncio.sleep(10)
        print("Ping servers...")
        table = Table(show_header=True, header_style="bold magenta")
        table.add_column("ID", style="dim", width=12)
        table.add_column("IP + Port", min_width=20)
        table.add_column("Status", justify="right")
        table.add_column("Ping (ms)", justify="right")

        try:
            with db_connection.cursor(dictionary=True) as cursor:
                cursor.execute("SELECT `ID`, `IP`, `ApiPort` FROM `servers`")
                servers = cursor.fetchall()
                for server in servers:
                    is_reachable, response_time_ms = await is_server_reachable(server['IP'], server['ApiPort'])
                    status_color = "green" if is_reachable else "red"
                    ping_color = "yellow" if is_reachable and response_time_ms > 30 else status_color
                    status_text = "[bold {}]{}[/]".format(status_color, "Online" if is_reachable else "Offline")
                    ping_text = "[bold {}]{} ms[/]".format(ping_color, response_time_ms) if response_time_ms is not None else "N/A"
                    table.add_row(str(server['ID']), f"{server['IP']}:{server['ApiPort']}", status_text, ping_text)

            console.print(table)

        except Error as e:
            print(f"Database error: {e}")

        await asyncio.sleep(20)













# Pydantic Model zur Validierung der eingehenden Daten
class Server(BaseModel):
    ram: int
    cpu: int
    storage: int
    port: int

# API-Endpunkt für Serverregistrierung
@app.post("/register-server/")
async def register_server(server: Server, request: Request, ):
    server_ip = request.client.host
    server_port = server.port
    cursor = db_connection.cursor(dictionary=True)
    try:
        # Überprüfen, ob der Server bereits registriert ist
        cursor.execute("SELECT `ID` FROM `servers` WHERE `IP` = %s AND `ApiPort` = %s", (server_ip, server_port))
        server_entry = cursor.fetchone()
        if server_entry:
            return {"status": "ok", "message": "Server already registered."}
        else:
            cursor.execute(
                "INSERT INTO `servers` (`IP`, `ApiPort`, `ram`, `cpu`, `storage`) VALUES (%s, %s, %s, %s, %s)",
                (server_ip, server_port, server.ram, server.cpu, server.storage)
            )
            db_connection.commit()
            return {"status": "ok", "message": "Server registered successfully."}
    except Error as e:
        db_connection.rollback()
        raise HTTPException(status_code=500, detail=f"Database error: {e}")
    finally:
        cursor.close()














class VMCreateSpecs(BaseModel):
    template_name: str
    ram: int
    cpus: int
    storage: int
    token: str

# API-Endpunkt, um den besten Server auszuwählen und die VM zu erstellen
@app.post("/select-server-and-create-vm/")
async def select_server_and_create_vm(specs: VMCreateSpecs, request: Request):

    cursor = db_connection.cursor(dictionary=True)
    try:
        # Abfrage, um den besten Server zu wählen und die Server-ID zu erhalten
        cursor.execute("SELECT `ID`, `IP`, `ApiPort` FROM `servers` ORDER BY `cpu` DESC LIMIT 1")
        best_server = cursor.fetchone()
        if best_server:
            # API-Aufruf an den besten Server senden, um die VM zu erstellen, inklusive der Server-ID
            create_vm_endpoint = f"http://{best_server['IP']}:{best_server['ApiPort']}/create-vm/"
            response = post(create_vm_endpoint, json={
                "server_id": best_server['ID'],  # Füge hier die Server-ID hinzu
                "template_name": specs.template_name,
                "ram": specs.ram,
                "cpus": specs.cpus,
                "storage": specs.storage,
                "token": specs.token
            })
            if response.status_code == 200:
                return {"status": "success",
                        "message": "VM creation initiated on the selected server with ID " + str(best_server['ID'])}
            else:
                raise HTTPException(status_code=response.status_code, detail=response.json())
        else:
            raise HTTPException(status_code=404, detail="No suitable server found.")
    except Error as e:
        db_connection.rollback()
        raise HTTPException(status_code=500, detail=f"Database error: {e}")
    finally:
        cursor.close()



















class VMControlRequest(BaseModel):
    vm_name: str
    token: str


@app.post("/start-vm/")
async def start_vm(vm_control_request: VMControlRequest):

    try:
        server_info = get_server_info_by_vm_name(vm_control_request.vm_name, vm_control_request.token)
        if not server_info:
            raise HTTPException(status_code=404, detail="Server information could not be found.")
        response = post(
            f"http://{server_info['IP']}:{server_info['ApiPort']}/start-vm/",
            json={
                "vm_name": vm_control_request.vm_name,
                "token": vm_control_request.token
            }
        )
        if response.status_code == 200:
            return response.json()
        else:
            raise HTTPException(status_code=response.status_code, detail=response.json())
    except HTTPException as e:
        raise e

@app.post("/stop-vm/")
async def stop_vm(vm_control_request: VMControlRequest):
    server_info = get_server_info_by_vm_name(vm_control_request.vm_name, vm_control_request.token)
    try:
        response = post(
            f"http://{server_info['IP']}:{server_info['ApiPort']}/stop-vm/",
            json={
                "vm_name": vm_control_request.vm_name,
                "token": vm_control_request.token
            }
        )
        if response.status_code == 200:
            return response.json()
        else:
            raise HTTPException(status_code=response.status_code, detail=response.json())
    except HTTPException as e:
        raise e

@app.post("/restart-vm/")
async def restart_vm(vm_control_request: VMControlRequest):
    server_info = get_server_info_by_vm_name(vm_control_request.vm_name, vm_control_request.token)
    try:
        response = post(
            f"http://{server_info['IP']}:{server_info['ApiPort']}/restart-vm/",
            json={
                "vm_name": vm_control_request.vm_name,
                "token": vm_control_request.token
            }
        )
        if response.status_code == 200:
            return response.json()
        else:
            raise HTTPException(status_code=response.status_code, detail=response.json())
    except HTTPException as e:
        raise e







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



def get_server_info_by_vm_name(vm_name: str, token: str):
    # Benutzer-ID anhand des Tokens holen
    owner_id = get_user_by_token(token)

    # Erste Abfrage, um VM-Informationen zu holen
    try:
        with db_connection.cursor(dictionary=True) as cursor:
            cursor.execute(
                "SELECT `ID`, `name`, `ownerID`, `IP`, `ram`, `cpu`, `storage`, `RootPw`, `serverid` "
                "FROM `vms` "
                "WHERE `name` = %s", (vm_name,)
            )
            vm_info = cursor.fetchone()
            if vm_info is None:
                raise HTTPException(status_code=404, detail="VM not found")

            # Überprüfen, ob die VM dem Benutzer gehört
            if int(vm_info['ownerID']) != owner_id:
                raise HTTPException(status_code=403, detail="VM does not belong to the user")

            # Zweite Abfrage, um Serverinformationen zu holen
            cursor.execute(
                "SELECT `ID`, `IP`, `ApiPort`, `ram`, `cpu`, `storage` "
                "FROM `servers` "
                "WHERE `ID` = %s", (vm_info['serverid'],)
            )
            server_info = cursor.fetchone()
            if server_info is None:
                raise HTTPException(status_code=404, detail="Server not found for VM")

    except Error as e:
        raise HTTPException(status_code=500, detail=f"Database error: {e}")

    # Kombiniere VM- und Serverinformationen, falls erforderlich
    combined_info = {**vm_info, **server_info}

    return combined_info




@app.on_event("startup")
async def startup_event():
    asyncio.create_task(ping_all_servers())




if __name__ == "__main__":
    import uvicorn

    uvicorn.run(app, host="0.0.0.0", port=8000)




