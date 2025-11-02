# Forum User Types - Visual Examples

## 📊 API Response Examples

### Example 1: Question by Admin

```json
GET /api/v1/forum/questions/1

{
  "id": 1,
  "title": "Official Announcement: New Training Program",
  "body": "We are launching a new organic farming training program...",
  "user": {
    "id": 2,
    "first_name": "Sarah",
    "last_name": "Administrator",
    "username": "sarah_admin",
    "profile_photo": "users/sarah.jpg",
    "profile_photo_url": "http://localhost:8000/storage/profile_photos/users/sarah.jpg",
    "user_type": "admin"  // ⭐ USE THIS TO HIGHLIGHT
  },
  "answers": []
}
```

**Frontend Display:**
```
┌────────────────────────────────────────────────┐
│ 🔴 ADMIN POST (Red highlight)                  │
├────────────────────────────────────────────────┤
│ 👤 [Photo]  Sarah Administrator  👑 Admin     │
│             @sarah_admin                       │
│                                                │
│ 📌 Official Announcement: New Training Program │
│                                                │
│ We are launching a new organic farming         │
│ training program...                            │
└────────────────────────────────────────────────┘
```

---

### Example 2: Question by Trainer with Answers

```json
GET /api/v1/forum/questions/2

{
  "id": 2,
  "title": "Best Practices for Irrigation",
  "body": "Let me share some expert tips on irrigation...",
  "user": {
    "id": 3,
    "first_name": "John",
    "last_name": "Expert",
    "username": "john_trainer",
    "profile_photo": "users/john.jpg",
    "profile_photo_url": "http://localhost:8000/storage/profile_photos/users/john.jpg",
    "user_type": "trainer"  // ⭐ USE THIS TO HIGHLIGHT
  },
  "answers": [
    {
      "id": 1,
      "body": "Thank you for this valuable information!",
      "user": {
        "id": 5,
        "first_name": "Ali",
        "last_name": "Farmer",
        "username": "ali_farmer",
        "profile_photo": "users/ali.jpg",
        "profile_photo_url": "http://localhost:8000/storage/profile_photos/users/ali.jpg",
        "user_type": "farmer"  // Regular user
      }
    },
    {
      "id": 2,
      "body": "I agree with John's approach. Additional tip...",
      "user": {
        "id": 2,
        "first_name": "Sarah",
        "last_name": "Administrator",
        "username": "sarah_admin",
        "profile_photo": "users/sarah.jpg",
        "profile_photo_url": "http://localhost:8000/storage/profile_photos/users/sarah.jpg",
        "user_type": "admin"  // ⭐ ADMIN ANSWER - HIGHLIGHT!
      }
    }
  ]
}
```

**Frontend Display:**
```
┌────────────────────────────────────────────────┐
│ 🔵 TRAINER POST (Blue highlight)               │
├────────────────────────────────────────────────┤
│ 👤 [Photo]  John Expert  ⭐ Expert             │
│             @john_trainer                      │
│                                                │
│ 📌 Best Practices for Irrigation               │
│                                                │
│ Let me share some expert tips on irrigation... │
├────────────────────────────────────────────────┤
│ 💬 2 Answers                                   │
│                                                │
│  ┌──────────────────────────────────────────┐ │
│  │ 👤 [Photo] Ali Farmer                    │ │
│  │           @ali_farmer                    │ │
│  │                                          │ │
│  │ Thank you for this valuable information! │ │
│  └──────────────────────────────────────────┘ │
│                                                │
│  ┌──────────────────────────────────────────┐ │
│  │ 🔴 ADMIN ANSWER (Red highlight)          │ │
│  ├──────────────────────────────────────────┤ │
│  │ 👤 [Photo] Sarah Administrator 👑 Admin  │ │
│  │           @sarah_admin                   │ │
│  │                                          │ │
│  │ I agree with John's approach.            │ │
│  │ Additional tip...                        │ │
│  └──────────────────────────────────────────┘ │
└────────────────────────────────────────────────┘
```

---

### Example 3: Cards API Response

```json
GET /api/v1/forum/cards?per_page=3

{
  "data": [
    {
      "id": 1,
      "title": "Official Announcement",
      "summary": "New training program launching...",
      "author": "Sarah Administrator",
      "author_user_type": "admin",  // ⭐ USE FOR HIGHLIGHTING
      "author_profile_photo": "users/sarah.jpg",
      "author_profile_photo_url": "http://localhost:8000/storage/profile_photos/users/sarah.jpg",
      "created_date": "2025-10-30",
      "created_time": "14:30",
      "views": 125,
      "comments": 8,
      "type": "announcement",
      "hashtags": ["training", "announcement"],
      "status": "open"
    },
    {
      "id": 2,
      "title": "Irrigation Best Practices",
      "summary": "Expert tips on irrigation systems...",
      "author": "John Expert",
      "author_user_type": "trainer",  // ⭐ USE FOR HIGHLIGHTING
      "author_profile_photo": "users/john.jpg",
      "author_profile_photo_url": "http://localhost:8000/storage/profile_photos/users/john.jpg",
      "created_date": "2025-10-30",
      "created_time": "13:15",
      "views": 89,
      "comments": 12,
      "type": "guide",
      "hashtags": ["irrigation", "tips"],
      "status": "open"
    },
    {
      "id": 3,
      "title": "Soil Quality Question",
      "summary": "How can I improve my soil...",
      "author": "Ali Farmer",
      "author_user_type": "farmer",  // Regular user
      "author_profile_photo": "users/ali.jpg",
      "author_profile_photo_url": "http://localhost:8000/storage/profile_photos/users/ali.jpg",
      "created_date": "2025-10-30",
      "created_time": "12:00",
      "views": 34,
      "comments": 3,
      "type": "question",
      "hashtags": ["soil", "help"],
      "status": "open"
    }
  ],
  "meta": {
    "total": 3,
    "per_page": 20
  }
}
```

**Frontend Display (Card Grid):**
```
┌──────────────────┐  ┌──────────────────┐  ┌──────────────────┐
│ 🔴 ADMIN         │  │ 🔵 TRAINER       │  │ REGULAR USER     │
├──────────────────┤  ├──────────────────┤  ├──────────────────┤
│ [Photo] S. Admin │  │ [Photo] J. Expert│  │ [Photo] A. Farmer│
│ 👑 Admin         │  │ ⭐ Expert        │  │                  │
│                  │  │                  │  │                  │
│ Official         │  │ Irrigation Best  │  │ Soil Quality     │
│ Announcement     │  │ Practices        │  │ Question         │
│                  │  │                  │  │                  │
│ New training     │  │ Expert tips on   │  │ How can I        │
│ program...       │  │ irrigation...    │  │ improve...       │
│                  │  │                  │  │                  │
│ 👁️ 125  💬 8     │  │ 👁️ 89   💬 12    │  │ 👁️ 34   💬 3     │
│ 🏷️ training      │  │ 🏷️ irrigation    │  │ 🏷️ soil          │
└──────────────────┘  └──────────────────┘  └──────────────────┘
```

---

## 🎨 Color Coding Guide

### Admin (`user_type: "admin"`)
- **Color:** Red (#ef4444)
- **Badge:** 👑 Admin
- **Border:** 4px solid red
- **Background:** Light red (#fef2f2)
- **Use Case:** Official announcements, moderation, system updates

### Trainer (`user_type: "trainer"`)
- **Color:** Blue (#3b82f6)
- **Badge:** ⭐ Expert
- **Border:** 4px solid blue
- **Background:** Light blue (#eff6ff)
- **Use Case:** Expert advice, professional guidance, tips

### Farmer (`user_type: "farmer"`)
- **Color:** Gray (default)
- **Badge:** None
- **Border:** 1px solid gray
- **Background:** White
- **Use Case:** Regular users, questions, community discussions

---

## 🔍 How to Use in Frontend

### React Example

```jsx
const QuestionCard = ({ question }) => {
  // Determine styling based on user_type
  const getCardStyle = () => {
    switch (question.user.user_type) {
      case 'admin':
        return {
          borderColor: '#ef4444',
          bgColor: '#fef2f2',
          badge: '👑 Admin',
          badgeBg: '#fee2e2',
          badgeColor: '#991b1b'
        };
      case 'trainer':
        return {
          borderColor: '#3b82f6',
          bgColor: '#eff6ff',
          badge: '⭐ Expert',
          badgeBg: '#dbeafe',
          badgeColor: '#1e40af'
        };
      default:
        return {
          borderColor: '#d1d5db',
          bgColor: '#ffffff',
          badge: null,
          badgeBg: null,
          badgeColor: null
        };
    }
  };

  const style = getCardStyle();

  return (
    <div 
      className="question-card"
      style={{
        borderLeft: `4px solid ${style.borderColor}`,
        backgroundColor: style.bgColor
      }}
    >
      {/* User Header */}
      <div className="user-header">
        <img 
          src={question.user.profile_photo_url || '/default-avatar.png'} 
          alt={question.user.first_name}
          className="avatar"
        />
        <div>
          <div className="user-name">
            <span>{question.user.first_name} {question.user.last_name}</span>
            {style.badge && (
              <span 
                className="badge"
                style={{
                  backgroundColor: style.badgeBg,
                  color: style.badgeColor
                }}
              >
                {style.badge}
              </span>
            )}
          </div>
          <span className="username">@{question.user.username}</span>
        </div>
      </div>

      {/* Question Content */}
      <h3>{question.title}</h3>
      <p>{question.body}</p>
    </div>
  );
};
```

---

## 📱 Mobile Display Example

```
┌─────────────────────────────────┐
│ Forum Questions                 │
├─────────────────────────────────┤
│                                 │
│ ╔═════════════════════════════╗ │
│ ║ 🔴 ADMIN POST               ║ │
│ ╠═════════════════════════════╣ │
│ ║ [📷] Sarah Administrator    ║ │
│ ║      👑 Admin               ║ │
│ ║                             ║ │
│ ║ Official Announcement       ║ │
│ ║ New training program...     ║ │
│ ║                             ║ │
│ ║ 👁️ 125   💬 8   🕐 14:30    ║ │
│ ╚═════════════════════════════╝ │
│                                 │
│ ┌───────────────────────────┐   │
│ │ 🔵 TRAINER POST           │   │
│ ├───────────────────────────┤   │
│ │ [📷] John Expert          │   │
│ │      ⭐ Expert            │   │
│ │                           │   │
│ │ Irrigation Best Practices │   │
│ │ Expert tips on...         │   │
│ │                           │   │
│ │ 👁️ 89   💬 12   🕐 13:15   │   │
│ └───────────────────────────┘   │
│                                 │
│ ┌───────────────────────────┐   │
│ │ [📷] Ali Farmer           │   │
│ │                           │   │
│ │ Soil Quality Question     │   │
│ │ How can I improve...      │   │
│ │                           │   │
│ │ 👁️ 34   💬 3    🕐 12:00   │   │
│ └───────────────────────────┘   │
│                                 │
└─────────────────────────────────┘
```

---

## ✅ Implementation Checklist

- [ ] Check `user.user_type` field in API responses
- [ ] Apply red styling for `user_type === 'admin'`
- [ ] Apply blue styling for `user_type === 'trainer'`
- [ ] Show 👑 Admin badge for admins
- [ ] Show ⭐ Expert badge for trainers
- [ ] Use thicker borders for admin/trainer posts
- [ ] Apply background color highlights
- [ ] Include profile photos
- [ ] Test on mobile devices
- [ ] Ensure accessibility (color + icons + text)

---

## 🚀 Result

Users will now see:
1. ✅ **Admin posts stand out** - Red highlight + 👑 badge
2. ✅ **Trainer posts highlighted** - Blue highlight + ⭐ badge
3. ✅ **Regular posts normal** - Standard styling
4. ✅ **Profile photos everywhere** - Visual identity
5. ✅ **Names and surnames** - Full user information
6. ✅ **Usernames** - @username for identification

**The forum now looks professional with clear visual hierarchy!** 🎉

