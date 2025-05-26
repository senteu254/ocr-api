from fastapi import FastAPI, BackgroundTasks
from pydantic import BaseModel
import uuid
import time
import os

app = FastAPI()
ocr_jobs = {}

class OCRRequest(BaseModel):
    file_url: str

def fake_ocr_job(job_id, file_url):
    time.sleep(10)  # simulate OCR time
    ocr_jobs[job_id]['status'] = 'completed'
    ocr_jobs[job_id]['result'] = {
        'file_url': file_url,
        'text': 'This is a simulated OCR result.',
        'shipment_data': {
            'tracking_number': 'ABC123456789',
            'origin': 'Nairobi',
            'destination': 'Mombasa',
            'date': '2025-05-26'
        }
    }

@app.post("/start")
def start_ocr(request: OCRRequest, background_tasks: BackgroundTasks):
    job_id = str(uuid.uuid4())
    ocr_jobs[job_id] = {'status': 'processing', 'result': None}
    background_tasks.add_task(fake_ocr_job, job_id, request.file_url)
    return {"job_id": job_id}

@app.get("/status/{job_id}")
def check_status(job_id: str):
    job = ocr_jobs.get(job_id)
    if job:
        return job
    return {"error": "Job ID not found"}

@app.get("/")
def root():
    return {"message": "OCR API is running. Use /start to begin OCR and /status/{job_id} to check status."}

