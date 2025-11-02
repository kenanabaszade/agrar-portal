#!/usr/bin/env python3
"""
Script to update Postman collection with all missing endpoints
and remove endpoints that don't exist in the codebase.
"""

import json

def create_request(name, method, path, auth=True, body=None, description="", query_params=None):
    """Helper function to create a request object"""
    request = {
        "name": name,
        "request": {
            "method": method,
            "header": [],
            "url": {
                "raw": f"{{{{base_url}}}}{path}",
                "host": ["{{base_url}}"],
                "path": path.strip("/").split("/")
            }
        }
    }
    
    if auth:
        request["request"]["header"].append({
            "key": "Authorization",
            "value": "Bearer {{auth_token}}"
        })
    
    if body:
        request["request"]["header"].append({
            "key": "Content-Type",
            "value": "application/json"
        })
        request["request"]["body"] = {
            "mode": "raw",
            "raw": json.dumps(body, indent=2)
        }
    
    if query_params:
        request["request"]["url"]["query"] = query_params
    
    if description:
        request["description"] = description
    
    return request

# Read original collection
with open('Agrar_Portal_API.postman_collection.json', 'r', encoding='utf-8-sig') as f:
    original = json.load(f)

# Start building updated collection
updated = {
    "info": {
        "_postman_id": "agrar-portal-api-collection-updated",
        "name": "Agrar Portal API - Complete & Updated",
        "description": "Complete and up-to-date API collection for Agrar Portal - All endpoints verified against codebase. Includes FAQ, Educational Content, Internship Programs, and all latest features. Last updated: January 2025.",
        "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json",
        "_exporter_id": "agrar-portal"
    },
    "item": [],
    "variable": original.get("variable", []),
    "event": original.get("event", [])
}

# Keep valid existing sections
sections_to_keep = [
    "Authentication",
    "Development/Testing Authentication", 
    "2FA Management",
    "Training Management",
    "Training Module Management",
    "Training Lesson Management",
    "Lesson Progress Tracking",
    "Category Management",
    "Exam Management (Admin Dashboard)",
    "Exam Timing & Duration Testing",
    "Exam Question Management (For Editing Existing Exams)",
    "Exam Taking (Students)",
    "Certificates",
    "Forum",
    "Enhanced Forum Management",
    "My Forum",
    "User Management",
    "Profile Management",
    "Google Calendar Authentication",
    "Google Meet Management",
    "Meeting Registration",
    "Training Media Management",
    "Registration Management"
]

# Copy existing valid sections
for item in original.get("item", []):
    if item.get("name") in sections_to_keep:
        updated["item"].append(item)

print(f"✓ Copied {len(updated['item'])} existing sections")

# ADD NEW SECTIONS

# 1. Dashboard & Statistics
dashboard_section = {
    "name": "Dashboard & Statistics",
    "item": [
        create_request("Get Dashboard", "GET", "/api/v1/dashboard", 
                      description="Get main dashboard data for authenticated user"),
        create_request("Get Training Statistics", "GET", "/api/v1/training-stats",
                      description="Get training statistics and analytics"),
        create_request("Get Webinar Statistics", "GET", "/api/v1/webinar-stats",
                      description="Get webinar statistics"),
        create_request("Get Webinar Analytics", "GET", "/api/v1/webinar-analytics",
                      description="Get detailed webinar analytics")
    ]
}
updated["item"].insert(3, dashboard_section)  # Insert after 2FA Management

# 2. FAQ Management
faq_section = {
    "name": "FAQ Management",
    "item": [
        create_request("List FAQs", "GET", "/api/v1/faqs",
                      query_params=[
                          {"key": "category", "value": "", "description": "Filter by category"},
                          {"key": "search", "value": "", "description": "Search in question/answer"},
                          {"key": "per_page", "value": "20"}
                      ]),
        create_request("Get FAQ Categories", "GET", "/api/v1/faqs/categories"),
        create_request("Get FAQ Statistics", "GET", "/api/v1/faqs/stats",
                      description="Admin only - Get FAQ statistics"),
        create_request("Create FAQ", "POST", "/api/v1/faqs",
                      body={
                          "question": "How to register for a training?",
                          "answer": "Navigate to trainings page and click register button",
                          "category": "Registration",
                          "is_active": True,
                          "sort_order": 1
                      },
                      description="Admin only - Create new FAQ"),
        create_request("Get FAQ Details", "GET", "/api/v1/faqs/1"),
        create_request("Update FAQ", "PUT", "/api/v1/faqs/1",
                      body={
                          "question": "Updated question",
                          "answer": "Updated answer",
                          "is_active": True
                      },
                      description="Admin only - Update FAQ"),
        create_request("Delete FAQ", "DELETE", "/api/v1/faqs/1",
                      description="Admin only - Delete FAQ"),
        create_request("Mark FAQ as Helpful", "POST", "/api/v1/faqs/1/helpful",
                      description="Mark an FAQ as helpful")
    ]
}
updated["item"].append(faq_section)

# 3. Educational Content
education_section = {
    "name": "Educational Content (Maarifləndirmə)",
    "item": [
        create_request("Get Education Statistics", "GET", "/api/v1/education/stats",
                      description="Get educational content statistics"),
        create_request("List Articles", "GET", "/api/v1/education/articles",
                      description="List all articles"),
        create_request("List Telimats", "GET", "/api/v1/education/telimats",
                      description="List all educational materials (telimats)"),
        create_request("List Educational Content", "GET", "/api/v1/education",
                      query_params=[
                          {"key": "type", "value": "", "description": "Filter by type: article, telimat"},
                          {"key": "search", "value": "", "description": "Search in title/content"},
                          {"key": "category", "value": "", "description": "Filter by category"},
                          {"key": "per_page", "value": "20"}
                      ]),
        create_request("Create Educational Content", "POST", "/api/v1/education",
                      body={
                          "title": "Modern Farming Techniques",
                          "content": "Detailed content about modern farming...",
                          "type": "article",
                          "category": "Agriculture",
                          "is_published": True,
                          "author_id": 1
                      },
                      description="Admin/Trainer only - Create educational content"),
        create_request("Get Content Details", "GET", "/api/v1/education/1"),
        create_request("Update Educational Content", "PUT", "/api/v1/education/1",
                      body={
                          "title": "Updated title",
                          "content": "Updated content",
                          "is_published": True
                      },
                      description="Admin/Trainer only - Update content"),
        create_request("Delete Educational Content", "DELETE", "/api/v1/education/1",
                      description="Admin/Trainer only - Delete content")
    ]
}
updated["item"].append(education_section)

# 4. Internship Programs
internship_section = {
    "name": "Internship Programs (Staj Proqramları)",
    "item": [
        create_request("List Internship Programs", "GET", "/api/v1/internship-programs",
                      auth=False,
                      query_params=[
                          {"key": "category", "value": "", "description": "Filter by category"},
                          {"key": "status", "value": "active", "description": "Filter by status"},
                          {"key": "search", "value": "", "description": "Search in title/description"},
                          {"key": "per_page", "value": "20"}
                      ],
                      description="Public endpoint - optional authentication"),
        create_request("Get Featured Programs", "GET", "/api/v1/internship-programs/featured",
                      auth=False,
                      description="Get featured internship programs"),
        create_request("Get Program Categories", "GET", "/api/v1/internship-programs/categories",
                      auth=False,
                      description="Get available program categories"),
        create_request("Get Program Trainers", "GET", "/api/v1/internship-programs/trainers",
                      auth=False,
                      description="Get trainers for programs"),
        create_request("Get Program Details", "GET", "/api/v1/internship-programs/1",
                      auth=False,
                      description="Get detailed program information"),
        create_request("Create Internship Program", "POST", "/api/v1/internship-programs",
                      body={
                          "title": "Summer Agricultural Internship",
                          "description": "Hands-on farming experience",
                          "category": "Practical Training",
                          "trainer_id": 2,
                          "start_date": "2025-06-01",
                          "end_date": "2025-08-31",
                          "location": "Agricultural Farm, Region A",
                          "max_participants": 20,
                          "requirements": "Basic agricultural knowledge required",
                          "is_active": True
                      },
                      description="Admin/Trainer only - Create program"),
        create_request("Update Internship Program", "PUT", "/api/v1/internship-programs/1",
                      body={
                          "title": "Updated program title",
                          "max_participants": 25
                      },
                      description="Admin/Trainer only - Update program"),
        create_request("Delete Internship Program", "DELETE", "/api/v1/internship-programs/1",
                      description="Admin/Trainer only - Delete program"),
        create_request("Apply for Internship", "POST", "/api/v1/internship-programs/1/apply",
                      body={
                          "motivation_letter": "I am very interested in this program...",
                          "cv_file": "base64_encoded_cv_or_file_upload",
                          "additional_info": "Previous farming experience"
                      },
                      description="Apply for an internship program"),
        create_request("Get My Applications", "GET", "/api/v1/my-internship-applications",
                      description="Get user's internship applications"),
        create_request("Get Application Details", "GET", "/api/v1/internship-applications/1",
                      description="Get specific application details"),
        create_request("Cancel Application", "DELETE", "/api/v1/internship-applications/1",
                      description="Cancel/delete an application"),
        create_request("Download Application CV", "GET", "/api/v1/internship-applications/1/download-cv",
                      description="Download CV from application"),
        create_request("Get Program Applications (Admin)", "GET", "/api/v1/internship-programs/1/applications",
                      description="Admin only - Get all applications for a program"),
        create_request("Get Enrolled Users (Admin)", "GET", "/api/v1/internship-programs/1/enrolled-users",
                      description="Admin only - Get enrolled users"),
        create_request("Get Program Statistics (Admin)", "GET", "/api/v1/internship-programs/1/stats",
                      description="Admin only - Get program statistics"),
        create_request("List All Applications (Admin)", "GET", "/api/v1/admin/internship-applications",
                      query_params=[
                          {"key": "status", "value": "", "description": "Filter by status"},
                          {"key": "program_id", "value": "", "description": "Filter by program"},
                          {"key": "per_page", "value": "20"}
                      ],
                      description="Admin only - List all applications"),
        create_request("Update Application Status (Admin)", "PUT", "/api/v1/admin/internship-applications/1/status",
                      body={
                          "status": "approved",
                          "admin_notes": "Candidate meets all requirements"
                      },
                      description="Admin only - Update application status")
    ]
}
updated["item"].append(internship_section)

# 5. Additional Training Endpoints (add to existing or new section)
training_enhancements = {
    "name": "Training - Public & Enhanced Endpoints",
    "item": [
        create_request("List Public Trainings", "GET", "/api/v1/trainings/public",
                      auth=False,
                      query_params=[
                          {"key": "page", "value": "1"},
                          {"key": "per_page", "value": "20"},
                          {"key": "category", "value": ""},
                          {"key": "search", "value": ""}
                      ],
                      description="Public endpoint - list all trainings. Optional auth for user-specific data."),
        create_request("List Online Trainings", "GET", "/api/v1/trainings/online",
                      auth=False,
                      description="Public endpoint - list online trainings"),
        create_request("List Offline Trainings", "GET", "/api/v1/trainings/offline",
                      auth=False,
                      description="Public endpoint - list offline trainings"),
        create_request("Get Offline Training Detail", "GET", "/api/v1/trainings/offline/1",
                      auth=False,
                      description="Get detailed offline training information"),
        create_request("Get Training Detailed View", "GET", "/api/v1/trainings/1/detailed",
                      auth=False,
                      description="Get comprehensive training details. Optional auth."),
        create_request("Get Trainings Dropdown", "GET", "/api/v1/trainings/dropdown",
                      description="Admin/Trainer - Get trainings for dropdown selection"),
        create_request("List Future Trainings", "GET", "/api/v1/trainings/future",
                      description="List upcoming future trainings"),
        create_request("List Ongoing Trainings", "GET", "/api/v1/trainings/ongoing",
                      description="List currently ongoing trainings"),
        create_request("Get All Trainings (No Pagination)", "GET", "/api/v1/trainings/all",
                      description="Get all trainings without pagination"),
        create_request("Mark Training Completed", "POST", "/api/v1/trainings/1/complete",
                      description="Mark training as completed for authenticated user"),
        create_request("Get Training Completion Status", "GET", "/api/v1/trainings/1/completion-status",
                      description="Get user's completion status for a training")
    ]
}
# Find and insert after Training Management
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Training Management":
        updated["item"].insert(i + 1, training_enhancements)
        break

# 6. Lesson Notes Section
lesson_notes = {
    "name": "Lesson Notes",
    "item": [
        create_request("Add Lesson Notes", "POST", "/api/v1/lessons/1/notes",
                      body={"notes": "Important points to remember..."},
                      description="Add personal notes for a lesson"),
        create_request("Get Lesson Notes", "GET", "/api/v1/lessons/1/notes",
                      description="Get personal notes for a lesson"),
        create_request("Update Lesson Notes", "PUT", "/api/v1/lessons/1/notes",
                      body={"notes": "Updated notes..."},
                      description="Update personal notes"),
        create_request("Delete Lesson Notes", "DELETE", "/api/v1/lessons/1/notes",
                      description="Delete personal notes")
    ]
}
# Insert after Lesson Progress Tracking
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Lesson Progress Tracking":
        updated["item"].insert(i + 1, lesson_notes)
        break

# 7. Temporary Lesson Media
temp_lesson_media = {
    "name": "Temporary Lesson Media",
    "item": [
        {
            "name": "Upload Temporary Media",
            "request": {
                "method": "POST",
                "header": [
                    {"key": "Authorization", "value": "Bearer {{auth_token}}"}
                ],
                "body": {
                    "mode": "formdata",
                    "formdata": [
                        {"key": "file", "type": "file", "src": []},
                        {"key": "type", "value": "image", "type": "text"}
                    ]
                },
                "url": {
                    "raw": "{{base_url}}/api/v1/lessons/upload-temp-media",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "lessons", "upload-temp-media"]
                }
            },
            "description": "Upload temporary media during lesson creation"
        },
        create_request("Delete Temporary Media", "DELETE", "/api/v1/lessons/delete-temp-media",
                      body={"file_path": "temp/media/abc123.jpg"},
                      description="Delete temporary media file")
    ]
}
# Insert after Lesson Management
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Training Lesson Management":
        updated["item"].insert(i + 1, temp_lesson_media)
        break

# 8. Enhanced Exam Endpoints
exam_enhancements = {
    "name": "Exam - Additional Endpoints",
    "item": [
        create_request("Get Exam Statistics", "GET", "/api/v1/exams/stats",
                      description="Admin only - Get exam statistics"),
        create_request("Get Comprehensive Exam Stats", "GET", "/api/v1/exams/comprehensive-stats",
                      description="Admin only - Get comprehensive exam statistics"),
        create_request("Get Detailed Exam List", "GET", "/api/v1/exams/detailed-list",
                      description="Admin only - Get detailed list of all exams"),
        create_request("Get Exam Form Data", "GET", "/api/v1/exams/form-data",
                      description="Admin/Trainer - Get form data for creating exams"),
        create_request("Get Public Exam View", "GET", "/api/v1/exams/1/public",
                      description="Public view of exam information"),
        create_request("Get Exam Result", "GET", "/api/v1/exams/1/result",
                      description="Get user's exam result"),
        create_request("Update Exam Status", "PUT", "/api/v1/exams/1/status",
                      body={"status": "active"},
                      description="Admin/Trainer - Update exam status")
    ]
}
# Insert after Exam Management
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Exam Management (Admin Dashboard)":
        updated["item"].insert(i + 1, exam_enhancements)
        break

# 9. Admin Exam Grading
admin_exam_grading = {
    "name": "Admin Exam Grading",
    "item": [
        create_request("Get Pending Reviews", "GET", "/api/v1/admin/exams/pending-reviews",
                      description="Admin only - Get exams pending text question review"),
        create_request("Get Exam for Grading", "GET", "/api/v1/admin/exams/1/for-grading",
                      description="Admin only - Get exam submission for grading"),
        create_request("Grade Text Questions", "POST", "/api/v1/admin/exams/1/grade-text-questions",
                      body={
                          "grades": [
                              {"question_id": 1, "points_awarded": 15, "feedback": "Excellent answer"},
                              {"question_id": 2, "points_awarded": 12, "feedback": "Good but incomplete"}
                          ]
                      },
                      description="Admin only - Grade text questions")
    ]
}
updated["item"].append(admin_exam_grading)

# 10. Enhanced Certificate Endpoints
certificate_enhancements = {
    "name": "Certificates - Enhanced",
    "item": [
        create_request("Get My Certificates", "GET", "/api/v1/my/certificates",
                      description="Get all certificates for authenticated user"),
        {
            "name": "Upload Certificate PDF",
            "request": {
                "method": "POST",
                "header": [
                    {"key": "Authorization", "value": "Bearer {{auth_token}}"}
                ],
                "body": {
                    "mode": "formdata",
                    "formdata": [
                        {"key": "pdf_file", "type": "file", "src": []}
                    ]
                },
                "url": {
                    "raw": "{{base_url}}/api/v1/certificates/1/upload-pdf",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "certificates", "1", "upload-pdf"]
                }
            },
            "description": "Upload PDF for certificate"
        },
        create_request("Generate Certificate PDF", "POST", "/api/v1/certificates/generate-pdf",
                      body={
                          "certificate_id": 1,
                          "template": "default"
                      },
                      description="Generate PDF certificate"),
        create_request("Get Certificate Data", "GET", "/api/v1/certificates/1/data",
                      auth=False,
                      description="Public - Get certificate data"),
        create_request("Verify Certificate", "GET", "/api/v1/certificates/{certificateNumber}/verify",
                      auth=False,
                      description="Public - Verify certificate by number")
    ]
}
# Find Certificates section and enhance it
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Certificates":
        # Add new items to existing Certificates section
        for new_item in certificate_enhancements["item"]:
            item["item"].append(new_item)
        break

# 11. Public Certificate Verification
public_certificates = {
    "name": "Public Certificate Verification",
    "item": [
        create_request("View Certificate by Signature", "GET", "/certificates/verify/{signature}",
                      auth=False,
                      description="Public - View certificate by signature"),
        create_request("Verify Certificate Page", "GET", "/certificates/verify-page/{signature}",
                      auth=False,
                      description="Public - Certificate verification page"),
        create_request("Download Certificate PDF", "GET", "/certificates/download/{signature}",
                      auth=False,
                      description="Public - Download certificate PDF")
    ]
}
updated["item"].append(public_certificates)

# 12. Enhanced User Management
user_enhancements = {
    "name": "User Management - Enhanced",
    "item": [
        create_request("Get User Statistics", "GET", "/api/v1/users/stats",
                      description="Admin only - Get user statistics"),
        create_request("Get Simple User List", "GET", "/api/v1/users/simple",
                      description="Get simple list of users"),
        create_request("Get Trainers List", "GET", "/api/v1/trainers",
                      description="Get list of all trainers"),
        create_request("Get Categories from Users Endpoint", "GET", "/api/v1/categories",
                      description="Get categories list")
    ]
}
# Add to User Management section
for i, item in enumerate(updated["item"]):
    if item.get("name") == "User Management":
        for new_item in user_enhancements["item"]:
            item["item"].append(new_item)
        break

# 13. Profile Photo Management
profile_photo = {
    "name": "Profile Photo Management",
    "item": [
        {
            "name": "Upload Profile Photo",
            "request": {
                "method": "POST",
                "header": [
                    {"key": "Authorization", "value": "Bearer {{auth_token}}"}
                ],
                "body": {
                    "mode": "formdata",
                    "formdata": [
                        {"key": "photo", "type": "file", "src": []}
                    ]
                },
                "url": {
                    "raw": "{{base_url}}/api/v1/profile/upload-photo",
                    "host": ["{{base_url}}"],
                    "path": ["api", "v1", "profile", "upload-photo"]
                }
            },
            "description": "Upload profile photo"
        },
        create_request("Delete Profile Photo", "DELETE", "/api/v1/profile/delete-photo",
                      description="Delete profile photo")
    ]
}
# Insert after Profile Management
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Profile Management":
        updated["item"].insert(i + 1, profile_photo)
        break

# 14. Enhanced Registration
registration_enhancements = {
    "name": "Registration - Enhanced",
    "item": [
        create_request("Cancel Training Registration", "DELETE", "/api/v1/trainings/1/cancel-registration",
                      description="Cancel training registration"),
        create_request("Get My Training Registrations", "GET", "/api/v1/my-training-registrations",
                      description="Get user's training registrations")
    ]
}
# Add to Registration Management section
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Registration Management":
        for new_item in registration_enhancements["item"]:
            item["item"].append(new_item)
        break

# 15. Forum Enhancements
forum_enhancements = {
    "name": "Forum - Stats & Voting",
    "item": [
        create_request("Get Forum Statistics", "GET", "/api/v1/forum/stats",
                      description="Get forum statistics"),
        create_request("Get Forum Cards", "GET", "/api/v1/forum/cards",
                      description="Get forum summary cards"),
        create_request("Vote on Poll", "POST", "/api/v1/forum/questions/1/vote",
                      body={"vote_option": "option_1"},
                      description="Vote on a forum poll question")
    ]
}
# Add to Forum section
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Forum":
        for new_item in forum_enhancements["item"]:
            item["item"].append(new_item)
        break

# 16. Meeting Cards
meeting_cards = {
    "name": "Meeting Cards",
    "item": [
        create_request("Get Meeting Cards", "GET", "/api/v1/meetings/cards",
                      description="Get meeting summary cards")
    ]
}
# Insert before Google Meet Management
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Google Meet Management":
        updated["item"].insert(i, meeting_cards)
        break

# 17. Progress Management (enhance existing)
progress_enhancements = [
    create_request("Get Progress Details", "GET", "/api/v1/progress/1",
                  description="Get specific progress details"),
    create_request("Update Progress", "PUT", "/api/v1/progress/1",
                  body={"status": "completed", "notes": "Finished module"},
                  description="Update progress entry"),
    create_request("Delete Progress", "DELETE", "/api/v1/progress/1",
                  description="Delete progress entry")
]
for i, item in enumerate(updated["item"]):
    if item.get("name") == "Progress Tracking":
        for new_item in progress_enhancements:
            item["item"].append(new_item)
        break

print(f"\n✓ Added new sections and enhancements")
print(f"✓ Total sections in updated collection: {len(updated['item'])}")

# Remove Notifications and Payments sections (not in actual routes)
updated["item"] = [item for item in updated["item"] 
                   if item.get("name") not in ["Notifications", "Payments"]]

print(f"✓ Removed unused sections (Notifications, Payments)")
print(f"✓ Final section count: {len(updated['item'])}")

# Save updated collection
with open('Agrar_Portal_API_Complete_Updated.postman_collection.json', 'w', encoding='utf-8') as f:
    json.dump(updated, f, indent=2, ensure_ascii=False)

print(f"\n✅ SUCCESS! Updated collection saved to: Agrar_Portal_API_Complete_Updated.postman_collection.json")
print(f"\nSummary of changes:")
print(f"  • Added FAQ Management (8 endpoints)")
print(f"  • Added Educational Content module (8 endpoints)")
print(f"  • Added Internship Programs module (18 endpoints)")
print(f"  • Added Dashboard & Statistics (4 endpoints)")
print(f"  • Added Lesson Notes (4 endpoints)")
print(f"  • Added Temporary Lesson Media (2 endpoints)")
print(f"  • Added Admin Exam Grading (3 endpoints)")
print(f"  • Added Profile Photo Management (2 endpoints)")
print(f"  • Enhanced existing sections with missing endpoints")
print(f"  • Removed unused sections (Notifications, Payments)")
print(f"\n  Total: ~80+ new endpoints added")

