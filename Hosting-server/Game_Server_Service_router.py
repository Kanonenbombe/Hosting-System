import asyncio
from multiprocessing import process
from unittest import result

from fastapi import APIRouter, Depends
from fastapi import HTTPException, Request
from pydantic import BaseModel
import uvicorn
import subprocess
import os
from mysql.connector import connect, Error
import time
import requests
import psutil
import shutil
from Config import Config

app = APIRouter()

vboxmanage_path = Config.vboxmanage_path
Gameservers_path = Config.Gameservers_path
main_api_url = Config.main_api_url
port = Config.port
serverart = Config.serverart
ALLOWED_IP = Config.ALLOWED_IP
db_config = Config.db_config
GameServerInstalationsDateien_path = Config.GameServerInstalationsDateien_phaf


try:
    db_connection = connect(**Config.db_config)
    print("Game_Server_Service_router_app: Datenbankverbindung erfolgreich hergestellt")
except Error as e:
    print(f"Game_Server_Service_router_app: Fehler beim Verbinden zur Datenbank: {e}")
    exit(1)  # Beendet das Programm, wenn keine Verbindung möglich ist






def get_public_ip_address():
    try:
        response = requests.get('https://api.ipify.org')
        return response.text
    except requests.RequestException as e:
        raise ValueError(f"Error fetching public IP address: {e}")


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






def generate_Gameserver_name():
    try:
        with db_connection.cursor(dictionary=True) as cursor:
            cursor.execute("SELECT MAX(CAST(SUBSTRING(name, 7) AS UNSIGNED)) as max_number FROM `gameservers` WHERE `name` LIKE 'server%'")
            result = cursor.fetchone()
            max_number = result['max_number'] if result['max_number'] else 10000000
            new_vm_name = f"server{max_number + 1}"
            return new_vm_name
    except Error as e:
        raise HTTPException(status_code=500, detail=f"Database error: {e}")








class GameserverCreateRequest(BaseModel):
    serverid: int
    gameid: int
    ram: int
    cpu: int
    slots: int
    token: str


@app.post("/create-Gameserver/", dependencies=[Depends(verify_ip)])
async def create_gameserver(gameserver_create_request: GameserverCreateRequest):
    user_id = get_user_by_token(gameserver_create_request.token)
    new_Gmaeserver_name = generate_Gameserver_name()

    server_directory = os.path.join(Gameservers_path, new_Gmaeserver_name)
    print(server_directory)

    try:
        public_ip_address = get_public_ip_address()
        print(public_ip_address)


    except subprocess.CalledProcessError as e:
        raise HTTPException(status_code=500, detail=f"Failed to get Gameserver IP address: {str(e)}")

    with db_connection.cursor() as cursor:
        cursor.execute(
            "INSERT INTO gameservers (name, GameID, ownerID, IP, ServerID, disable_reason, instalstatus) VALUES (%s,%s, %s, %s, %s, %s, %s)",
            (new_Gmaeserver_name, gameserver_create_request.gameid, user_id, public_ip_address,
             gameserver_create_request.serverid, "", 1)
        )
        db_connection.commit()

    try:
        if not os.path.exists(Gameservers_path):
            os.makedirs(Gameservers_path)

        if not os.path.exists(server_directory):
            os.makedirs(server_directory)

        build_tools_path = os.path.join(GameServerInstalationsDateien_path, "BuildTools.jar")
        destination_path = os.path.join(server_directory, "BuildTools.jar")

        if os.path.isfile(build_tools_path):
            process = await asyncio.create_subprocess_exec(
                'java', f"-Xmx{gameserver_create_request.ram}G", '-jar', 'BuildTools.jar',
                cwd=server_directory,
                stdout=asyncio.subprocess.PIPE,
                stderr=asyncio.subprocess.PIPE
            )
            await process.wait()  # Warten auf den Abschluss des Prozesses

            with db_connection.cursor() as cursor:
                cursor.execute(
                    "UPDATE gameservers SET instalstatus = %s WHERE name = %s",
                    (0, new_Gmaeserver_name)
                )
                db_connection.commit()
        else:
            raise HTTPException(status_code=404, detail="BuildTools.jar not found")




        return {"status": "success", "message": f"Gamserver {new_Gmaeserver_name} created and saved in the database successfully."}
    except subprocess.CalledProcessError as e:
        raise HTTPException(status_code=500, detail=str(e))
    except Error as e:
        db_connection.rollback()
        raise HTTPException(status_code=500, detail=f"Database error: {e}")