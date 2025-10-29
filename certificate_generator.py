#!/usr/bin/env python3
"""
Certificate PDF Generator for Aqrar Portal
Generates certificates using the HTML template with dynamic data
"""

import os
import sys
import json
import hashlib
import qrcode
from datetime import datetime
from pathlib import Path
import base64
from selenium import webdriver
from selenium.webdriver.chrome.options import Options
from selenium.webdriver.chrome.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC
import time

class CertificateGenerator:
    def __init__(self):
        self.base_path = Path(__file__).parent
        self.certificate_dir = self.base_path / "certificate"
        self.output_dir = self.base_path / "storage" / "app" / "public" / "certificates"
        self.output_dir.mkdir(exist_ok=True)
        
        # Chrome options for headless PDF generation
        self.chrome_options = Options()
        self.chrome_options.add_argument("--headless")
        self.chrome_options.add_argument("--no-sandbox")
        self.chrome_options.add_argument("--disable-dev-shm-usage")
        self.chrome_options.add_argument("--disable-gpu")
        self.chrome_options.add_argument("--window-size=1200,800")
        
    def generate_digital_signature(self, user_data, exam_data):
        """Generate SHA256 digital signature for certificate"""
        # Create unique string from user and exam data
        signature_string = f"{user_data['id']}_{exam_data['id']}_{exam_data['title']}_{user_data['first_name']}_{user_data['last_name']}_{datetime.now().isoformat()}"
        
        # Generate SHA256 hash
        digital_signature = hashlib.sha256(signature_string.encode()).hexdigest()
        return digital_signature
    
    def generate_qr_code(self, pdf_url):
        """Generate QR code for certificate verification"""
        print(f"DEBUG: QR Code will contain URL: {pdf_url}", file=sys.stderr)
        qr = qrcode.QRCode(
            version=1,
            error_correction=qrcode.constants.ERROR_CORRECT_L,
            box_size=10,
            border=4,
        )
        qr.add_data(pdf_url)
        qr.make(fit=True)
        
        # Create QR code image
        qr_img = qr.make_image(fill_color="black", back_color="white")
        
        # Save QR code temporarily
        qr_path = self.output_dir / "temp_qr.png"
        qr_img.save(qr_path)
        
        return qr_path
    
    def create_certificate_html(self, user_data, exam_data, training_data, digital_signature):
        """Create HTML certificate with dynamic data"""
        
        # Generate PDF URL
        pdf_url = f"http://localhost:8000/storage/certificates/certificate_{digital_signature}.pdf"
        
        # Generate verification URL
        verification_url = f"http://localhost:8000/api/certificates/verify-page/{digital_signature}"
        
        # Generate QR code with PDF URL
        qr_path = self.generate_qr_code(pdf_url)
        
        # Read QR code as base64
        with open(qr_path, "rb") as qr_file:
            qr_base64 = base64.b64encode(qr_file.read()).decode()
        
        # Format date
        issue_date = datetime.now().strftime("%d / %m / %Y")
        
        # Generate certificate number
        cert_number = f"AZ-{datetime.now().year}-{user_data['id']:06d}"
        
        # Read template
        template_path = self.certificate_dir / "certificate.html"
        with open(template_path, 'r', encoding='utf-8') as f:
            html_content = f.read()
        
        # Replace placeholders with actual data
        replacements = {
            'CAHİD HÜMBƏTOV': f"{user_data['first_name'].upper()} {user_data['last_name'].upper()}",
            'AZ-2025-001234': cert_number,
            '27 / 10 / 2025': issue_date,
            'İşğaldan azad olunmuş ərazilərdə kənd təsərrüfatının potensialının gücləndirməsi modulları üzrə təlimlərdə imtahanı uğurla başa vurmuşdur.': exam_data.get('sertifikat_description', f"{training_data['title']} üzrə imtahanı uğurla başa vurmuşdur."),
            'PLACEHOLDER_QR_CODE': f"data:image/png;base64,{qr_base64}",
            'href=""': f'href="{verification_url}"',
            './image.png': str(self.certificate_dir / 'image.png')
        }
        
        # Debug: Print sertifikat_description
        print(f"DEBUG: Using sertifikat_description: {exam_data.get('sertifikat_description', 'Not provided')}", file=sys.stderr)
        
        # Apply replacements
        for placeholder, replacement in replacements.items():
            if placeholder in html_content:
                print(f"DEBUG: Replacing '{placeholder}' with '{replacement[:50]}...'", file=sys.stderr)
                html_content = html_content.replace(placeholder, replacement)
            else:
                print(f"DEBUG: Placeholder '{placeholder}' not found in template", file=sys.stderr)
        
        # Save modified HTML
        temp_html_path = self.output_dir / f"temp_certificate_{digital_signature}.html"
        with open(temp_html_path, 'w', encoding='utf-8') as f:
            f.write(html_content)
        
        # Clean up QR code
        qr_path.unlink()
        
        return temp_html_path, verification_url
    
    def generate_pdf(self, html_path, output_path):
        """Generate PDF from HTML using Chrome headless"""
        try:
            # Initialize Chrome driver
            driver = webdriver.Chrome(options=self.chrome_options)
            
            # Load HTML file
            file_url = f"file://{html_path.absolute()}"
            driver.get(file_url)
            
            # Wait for page to load
            WebDriverWait(driver, 10).until(
                EC.presence_of_element_located((By.CLASS_NAME, "container"))
            )
            
            # Generate PDF
            pdf_options = {
                'printBackground': True,
                'paperFormat': 'A4',
                'marginTop': 0,
                'marginBottom': 0,
                'marginLeft': 0,
                'marginRight': 0
            }
            
            pdf_data = driver.execute_cdp_cmd('Page.printToPDF', pdf_options)
            
            # Save PDF
            with open(output_path, 'wb') as f:
                f.write(base64.b64decode(pdf_data['data']))
            
            driver.quit()
            return True
            
        except Exception as e:
            print(f"Error generating PDF: {e}")
            if 'driver' in locals():
                driver.quit()
            return False
    
    def generate_certificate(self, user_data, exam_data, training_data):
        """Main method to generate certificate"""
        try:
            # Generate digital signature
            digital_signature = self.generate_digital_signature(user_data, exam_data)
            
            # Create HTML certificate
            html_path, verification_url = self.create_certificate_html(
                user_data, exam_data, training_data, digital_signature
            )
            
            # Generate PDF
            pdf_path = self.output_dir / f"certificate_{digital_signature}.pdf"
            success = self.generate_pdf(html_path, pdf_path)
            
            # Clean up temporary HTML
            html_path.unlink()
            
            if success:
                return {
                    'success': True,
                    'digital_signature': digital_signature,
                    'pdf_path': f"certificates/certificate_{digital_signature}.pdf",
                    'verification_url': verification_url,
                    'certificate_number': f"AZ-{datetime.now().year}-{user_data['id']:06d}"
                }
            else:
                return {'success': False, 'error': 'PDF generation failed'}
                
        except Exception as e:
            return {'success': False, 'error': str(e)}

def main():
    """Main function for command line usage"""
    if len(sys.argv) < 2:
        print("Usage: python certificate_generator.py <json_data_or_file>")
        print("       python certificate_generator.py --file <json_file>")
        sys.exit(1)
    
    try:
        # Check if it's a file input
        if sys.argv[1] == "--file" and len(sys.argv) == 3:
            # Read from file
            with open(sys.argv[2], 'r', encoding='utf-8') as f:
                json_string = f.read().strip()
        else:
            # Use command line argument
            json_string = sys.argv[1]
        
        # Debug: print the input string
        print(f"DEBUG: Input string: {json_string[:100]}...", file=sys.stderr)
        print(f"DEBUG: Input string length: {len(json_string)}", file=sys.stderr)
        print(f"DEBUG: Input string type: {type(json_string)}", file=sys.stderr)
        print(f"DEBUG: First 10 chars: {repr(json_string[:10])}", file=sys.stderr)
        
        # Try to parse JSON directly first
        try:
            data = json.loads(json_string)
        except json.JSONDecodeError as e:
            # If fails, try to fix common issues
            print(f"DEBUG: JSON parse error: {e}", file=sys.stderr)
            print(f"DEBUG: String after processing: {json_string[:100]}...", file=sys.stderr)
            
            # Try to fix common JSON issues - PowerShell converts single quotes to double quotes
            json_string = json_string.replace("'", '"')
            # Fix property names that are not quoted
            import re
            json_string = re.sub(r'(\w+):', r'"\1":', json_string)
            # Fix string values that are not quoted
            json_string = re.sub(r':\s*([^",{\[\s][^,}\]]*?)(?=\s*[,}])', r': "\1"', json_string)
            try:
                data = json.loads(json_string)
            except json.JSONDecodeError as e2:
                print(f"DEBUG: Still failed after quote replacement: {e2}", file=sys.stderr)
                raise e2
        
        # Initialize generator
        generator = CertificateGenerator()
        
        # Generate certificate
        result = generator.generate_certificate(
            data['user'],
            data['exam'],
            data['training']
        )
        
        # Output result as JSON
        print(json.dumps(result))
        
    except Exception as e:
        print(json.dumps({'success': False, 'error': str(e)}))

if __name__ == "__main__":
    main()
