# WSL2: Windows və Linux Fərqi

## 🪟 Windows-da

### WSL2 nədir?
WSL2 (Windows Subsystem for Linux) - Windows-da Linux mühiti işlətmək üçün Microsoft-un texnologiyasıdır.

### Windows-da nə edirik?

1. **WSL2 quraşdırırıq** (Windows-da)
2. **WSL2-də Redis quraşdırırıq** (Linux mühitində)
3. **Laravel-dən istifadə edirik** (Windows-da)

```
Windows
  ├── Laravel (Windows-da)
  └── WSL2
      └── Redis (Linux mühitində)
```

### Windows-da konfiqurasiya

`.env` faylı:
```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

WSL2-də Redis işləyir, Windows-dakı Laravel ona qoşulur.

---

## 🐧 Linux-da

### Linux-da WSL2 lazımdırmı?
**XEYR!** Linux-da artıq Linux mühiti var, WSL2 lazım deyil.

### Linux-da nə edirik?

1. **Native Redis quraşdırırıq** (birbaşa Linux-da)
2. **Laravel-dən istifadə edirik** (Linux-da)

```
Linux
  ├── Laravel (Linux-da)
  └── Redis (Linux-da, native)
```

### Linux-da konfiqurasiya

`.env` faylı:
```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

**Eyni konfiqurasiya!** Kod dəyişmir.

---

## 📊 Müqayisə

| | Windows | Linux |
|---|---|---|
| **WSL2 lazımdırmı?** | ✅ Bəli | ❌ Xeyr |
| **Redis quraşdırma** | WSL2-də | Native |
| **Laravel konfiqurasiyası** | `REDIS_HOST=127.0.0.1` | `REDIS_HOST=127.0.0.1` |
| **Kod dəyişirmi?** | ❌ Xeyr | ❌ Xeyr |

---

## 🔄 Köçürmə Prosesi

### Windows-dan Linux-a köçürəndə:

1. **Kod eyni qalır** ✅
2. **`.env` faylı eyni qalır** ✅
3. **Yalnız Redis quraşdırma üsulu dəyişir:**
   - Windows: WSL2-də Redis
   - Linux: Native Redis

### Linux-da Redis quraşdırma:

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install redis-server -y
sudo systemctl start redis-server
sudo systemctl enable redis-server

# Test
redis-cli ping
# Cavab: PONG
```

**`.env` faylı eyni qalır:**
```env
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
```

---

## ✅ Nəticə

- **Windows-da:** WSL2 quraşdırın, Redis WSL2-də işləsin
- **Linux-da:** WSL2 lazım deyil, native Redis quraşdırın
- **Kod:** Hər iki halda eyni qalır
- **Konfiqurasiya:** Hər iki halda eyni qalır

**Sadəcə Redis quraşdırma üsulu dəyişir, başqa heç nə dəyişmir!**


