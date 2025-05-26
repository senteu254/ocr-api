from fastapi import FastAPI, BackgroundTasks
from pydantic import BaseModel
import uuid
import time

app = FastAPI()
ocr_jobs = {}

class OCRRequest(BaseModel):
    file_url: str

def fake_ocr_job(job_id, file_url):
    print(f"Starting OCR background job for {job_id}")
    time.sleep(10)  # simulate processing time
    ocr_jobs[job_id]['status'] = 'done'  # Make status 'done' to match PHP check
    ocr_jobs[job_id]['result'] = {
        'filename': file_url.split('/')[-1],
        'description': 'Sample description',
        'shipper': 'Sample shipper',
        'port_loading': 'Nairobi',
        'port_discharge': 'Mombasa',
        'bl_no': 'BL12345',
        'container_nos': 'CONT1234',
        'no_of_bags': '100',
        'gross_weight': '1000kg',
        'net_weight': '950kg',
        'packing': 'Bags',
        'ocr_raw_text': 'This is a simulated OCR result.'
    }
    print(f"OCR job {job_id} done.")

@app.post("/start")
def start_ocr(request: OCRRequest, background_tasks: BackgroundTasks):
    job_id = str(uuid.uuid4())
    print(f"Received OCR start request for {request.file_url} - job {job_id}")
    ocr_jobs[job_id] = {'status': 'processing', 'result': None}
    background_tasks.add_task(fake_ocr_job, job_id, request.file_url)
    return {"job_id": job_id}

@app.get("/status/{job_id}")
def check_status(job_id: str):
    job = ocr_jobs.get(job_id)
    if job:
        return {
            "status": job['status'],
            "data": job['result']
        }
    return {"error": "Job ID not found"}

@app.get("/")
def root():
    return {"message": "OCR API is running. Use /start to begin OCR and /status/{job_id} to check status."}
