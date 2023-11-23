from fastapi import APIRouter
from fastapi import HTTPException, Request

from pydantic import BaseModel
from mysql.connector import connect, Error
from requests import post
import requests
import asyncio
import time
from config import db_config

app = APIRouter()


try:
    db_connection = connect(**db_config)
    print("Datenbankverbindung erfolgreich hergestellt")
except Error as e:
    print(f"Fehler beim Verbinden zur Datenbank: {e}")
    exit(1)  # Beendet das Programm, wenn keine Verbindung möglich ist








class GameserverCreateSpecs(BaseModel):
    gameid: int
    ram: int
    cpu: int
    slots: int
    token: str



@app.post("/select-server-and-create-game-server/")
async def select_server_and_create_vm(specs: GameserverCreateSpecs, request: Request):
    print(str(specs.gameid) + str(specs.slots) + str(specs.ram) + str(specs.cpu) + specs.token)
    cursor = db_connection.cursor(dictionary=True)
    try:
        # Abfrage, um den besten Server zu wählen und die Server-ID zu erhalten
        cursor.execute("SELECT `ID`, `IP`, `ApiPort` FROM `servers` ORDER BY `cpu` DESC LIMIT 1")
        best_server = cursor.fetchone()
        if best_server:
            # API-Aufruf an den besten Server senden, um die VM zu erstellen, inklusive der Server-ID
            create_vm_endpoint = f"http://{best_server['IP']}:{best_server['ApiPort']}/create-Gameserver/"
            print(best_server['ID'])
            response = post(create_vm_endpoint, json={
                "serverid": best_server['ID'],  # Füge hier die Server-ID hinzu
                "gameid": specs.gameid,
                "ram": specs.ram,
                "cpu": specs.cpu,
                "slots": specs.slots,
                "token": specs.token
            })
            if response.status_code == 200:
                return {"status": "success",
                        "message": "Gameserver creation initiated on the selected server with ID " + str(best_server['ID'])}
            else:
                raise HTTPException(status_code=response.status_code, detail=response.json())
        else:
            raise HTTPException(status_code=404, detail="No suitable server found.")
    except Error as e:
        db_connection.rollback()
        raise HTTPException(status_code=500, detail=f"Database error: {e}")
    finally:
        cursor.close()


