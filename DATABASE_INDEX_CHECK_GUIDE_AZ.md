# Verilənlər Bazası İndeksləri Yoxlama Təlimatı

## Məsələ

Backend yavaş işləyir, çünki verilənlər bazasında lazımi indekslər yoxdur. Bu təlimat sizə indeksləri necə yoxlamağı və əlavə etməyi öyrədir.

## 🔍 İndeksləri Yoxlamaq

### 1. Bütün İndeksləri Görmək

```bash
php artisan db:check-indexes
```

Bu komanda:
- Bütün cədvəllərdəki mövcud indeksləri göstərir
- Çatışmayan indeksləri təyin edir
- Ümumi statistika verir

### 2. Yalnız Çatışmayan İndeksləri Görmək

```bash
php artisan db:check-indexes --missing
```

### 3. Müəyyən Cədvəli Yoxlamaq

```bash
php artisan db:check-indexes --table=trainings
```

### 4. Migration Kodu Alınması

Çatışmayan indeksləri əlavə etmək üçün migration kodu alın:

```bash
php artisan db:check-indexes --fix
```

Bu komanda sizə hazır migration kodu verəcək ki, onu kopyalayıb yeni migration faylına yapışdıra biləsiniz.

### 5. Sorğu Performansını Analiz Etmək

```bash
php artisan db:check-indexes --analyze
```

## 📊 Nəticələr

Komandanı işə saldıqdan sonra görəcəksiniz:

- ✅ **Mövcud indekslər**: Hər cədvəl üçün hansı indekslər var
- ❌ **Çatışmayan indekslər**: Hansı indekslər lazımdır
- 📈 **Statistika**: Ümumi say və çatışmayan indekslərin sayı

## 🚀 İndeksləri Əlavə Etmək

### Addım 1: Migration Yaratmaq

```bash
php artisan make:migration add_missing_performance_indexes
```

### Addım 2: Migration Kodunu Əlavə Etmək

`database/migrations/` qovluğunda yeni yaradılmış migration faylını açın və `--fix` komandasının verdiyi kodu əlavə edin.

Və ya hazır migration faylından istifadə edin:
- `database/migrations/2025_11_16_102449_add_missing_performance_indexes.php`

### Addım 3: Migration İşə Salmaq

```bash
php artisan migrate
```

⚠️ **Diqqət**: Production mühitində migration işə salmazdan əvvəl backup alın!

## 📋 Əlavə Ediləcək İndekslər

### Trainings Cədvəli
- `type` - Təlim növünə görə filtrləmə
- `status` - Statusa görə filtrləmə
- `end_date` - Bitmə tarixinə görə filtrləmə
- `type + start_date` - Növ və başlama tarixinə görə
- `status + start_date` - Status və başlama tarixinə görə
- `category + start_date` - Kateqoriya və başlama tarixinə görə

### Exams Cədvəli
- `status` - Statusa görə filtrləmə
- `end_date` - Bitmə tarixinə görə filtrləmə
- `status + start_date` - Status və başlama tarixinə görə
- `category + start_date` - Kateqoriya və başlama tarixinə görə

### Training Registrations Cədvəli
- `user_id` - İstifadəçiyə görə axtarış
- `training_id` - Təlimə görə axtarış
- `registration_date` - Qeydiyyat tarixinə görə
- `user_id + status` - İstifadəçi və statusa görə
- `training_id + status` - Təlim və statusa görə
- `user_id + registration_date` - İstifadəçi və qeydiyyat tarixinə görə

### Exam Registrations Cədvəli
- `user_id` - İstifadəçiyə görə axtarış
- `exam_id` - İmtahana görə axtarış
- `registration_date` - Qeydiyyat tarixinə görə
- `user_id + status` - İstifadəçi və statusa görə
- `exam_id + status` - İmtahan və statusa görə
- `user_id + registration_date` - İstifadəçi və qeydiyyat tarixinə görə

### Forum Questions Cədvəli
- `status` - Statusa görə filtrləmə
- `user_id + status` - İstifadəçi və statusa görə
- `status + created_at` - Status və yaradılma tarixinə görə

### Forum Answers Cədvəli
- `user_id` - İstifadəçiyə görə axtarış
- `question_id + created_at` - Sual və yaradılma tarixinə görə

### Notifications Cədvəli
- `user_id` - İstifadəçiyə görə axtarış
- `type` - Növə görə filtrləmə
- `is_read` - Oxunub-oxunmamasına görə filtrləmə
- `user_id + type` - İstifadəçi və növə görə

### User Training Progress Cədvəli
- `user_id` - İstifadəçiyə görə axtarış
- `training_id` - Təlimə görə axtarış
- `module_id` - Modula görə axtarış
- `lesson_id` - Dərsə görə axtarış
- `status` - Statusa görə filtrləmə
- `user_id + training_id` - İstifadəçi və təlimə görə
- `user_id + status` - İstifadəçi və statusa görə

## 🎯 Gözlənilən Performans Artımı

İndeksləri əlavə etdikdən sonra:

- **Sorğu sürəti**: 50-200ms → 5-20ms (75-90% sürətli)
- **WHERE klauzaları**: Daha sürətli işləyəcək
- **ORDER BY əməliyyatları**: Optimallaşdırılacaq
- **JOIN əməliyyatları**: Daha sürətli olacaq

## 🔧 Əlavə Yoxlamalar

### PostgreSQL üçün

İndekslərin düzgün işlədiyini yoxlamaq:

```sql
-- Müəyyən sorğu üçün plan yoxlama
EXPLAIN ANALYZE SELECT * FROM trainings WHERE status = 'published' ORDER BY start_date DESC;

-- İndeks istifadəsi yoxlama
SELECT 
    schemaname,
    tablename,
    indexname,
    idx_scan as index_scans,
    idx_tup_read as tuples_read,
    idx_tup_fetch as tuples_fetched
FROM pg_stat_user_indexes
WHERE tablename = 'trainings'
ORDER BY idx_scan DESC;
```

### MySQL üçün

```sql
-- Sorğu planı yoxlama
EXPLAIN SELECT * FROM trainings WHERE status = 'published' ORDER BY start_date DESC;

-- İndeks istifadəsi yoxlama
SHOW INDEX FROM trainings;
```

## 📝 Qeydlər

1. **İndekslər disk yeri tutur**: Hər indeks təxminən 50-500KB yer tuta bilər
2. **Yazma performansı**: İndekslər INSERT/UPDATE əməliyyatlarını bir qədər yavaşlatdıra bilər (5-10%), lakin oxuma performansı çox yaxşılaşır
3. **Migration rollback**: Əgər problem olarsa, `php artisan migrate:rollback` ilə geri qaytara bilərsiniz

## 🆘 Problemlər

### Migration xətası verirsə

1. Migration faylını yoxlayın
2. İndeks adlarının unikal olduğundan əmin olun
3. Cədvəllərin mövcud olduğunu yoxlayın

### Performans hələ də yavaşdırsa

1. `--analyze` seçimi ilə sorğu performansını yoxlayın
2. EXPLAIN ANALYZE ilə konkret sorğuları yoxlayın
3. Cache konfiqurasiyasını yoxlayın
4. Digər bottleneckləri axtarın (network, server resources)

## 📚 Əlavə Məlumat

- Laravel Migration Sənədləri: https://laravel.com/docs/migrations
- PostgreSQL İndekslər: https://www.postgresql.org/docs/current/indexes.html
- MySQL İndekslər: https://dev.mysql.com/doc/refman/8.0/en/optimization-indexes.html



