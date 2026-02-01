#!/bin/bash
# ============================================================
# Laravel Hard-Coded String Detector v2
#
# Jalankan dari root project Laravel:
#   bash find_hardcoded.sh
#
# Opsi:
#   bash find_hardcoded.sh --dir=app/Http/Controllers
#   bash find_hardcoded.sh --export=report.txt
# ============================================================

# --- Warna output ---
RED='\033[0;31m'
YELLOW='\033[1;33m'
GREEN='\033[0;32m'
CYAN='\033[0;36m'
BOLD='\033[1m'
DIM='\033[2m'
NC='\033[0m'

# --- Default config ---
SCAN_DIRS="app Modules routes resources config database"
SKIP_DIRS="vendor node_modules storage bootstrap/cache tests .git public"
EXTENSIONS="php"
EXPORT_FILE=""

# --- Parse argumen ---
for arg in "$@"; do
    case $arg in
        --dir=*)
            SCAN_DIRS="${arg#*=}"
            SCAN_DIRS="${SCAN_DIRS//,/ }"
            ;;
        --export=*)
            EXPORT_FILE="${arg#*=}"
            ;;
    esac
done

# --- Temp file untuk hasil ---
RESULT_FILE=$(mktemp)
TOTAL_FILES=0

# ============================================================
# FUNGSI SCAN
# ============================================================

scan_pattern() {
    local label="$1"
    local pattern="$2"
    local skip_pattern="$3"

    for dir in $SCAN_DIRS; do
        [ -d "$dir" ] || continue

        find "$dir" -type f -name "*.$EXTENSIONS" | while read -r file; do
            # Skip folder yang tidak boleh
            local should_skip=false
            for sd in $SKIP_DIRS; do
                if [[ "$file" == *"/$sd/"* ]]; then
                    should_skip=true
                    break
                fi
            done
            [ "$should_skip" = true ] && continue

            grep -n -E "$pattern" "$file" | while IFS=: read -r line_num line_content; do
                local trimmed
                trimmed=$(echo "$line_content" | sed 's/^[[:space:]]*//')

                # Skip baris komentar
                [[ "$trimmed" =~ ^// ]] && continue
                [[ "$trimmed" =~ ^\# ]] && continue
                [[ "$trimmed" =~ ^\* ]] && continue
                [[ "$trimmed" =~ ^\<\!-- ]] && continue

                # Skip jika ada pattern pengecualian
                if [ -n "$skip_pattern" ]; then
                    echo "$line_content" | grep -q -E "$skip_pattern" && continue
                fi

                echo "$label|$file|$line_num|$trimmed" >> "$RESULT_FILE"
            done
        done
    done
}

# ============================================================
# JALANKAN SEMUA SCAN
# ============================================================

# ------------------------------------------------------------
# 1. URL yang ditulis langsung TANPA env() / config()
#    Skip: localhost, CDN font, SVG namespace, boilerplate Laravel,
#          seeder/dummy, package default config, fallback di env()
# ------------------------------------------------------------
scan_pattern \
    "Hard-coded URL" \
    "['\"]https?://[^'\"]{5,}['\"]" \
    "env\(|config\(|localhost|fonts\.bunny\.net|fonts\.googleapis|w3\.org/2000/svg|laracasts\.com|laravel\.com|packagist\.org|github\.com|cdnjs|tailwindcss|placehold\.co|youtube\.com|welcome\.blade|example\.com|my-default-host|your-account-id|n\.widart"

# ------------------------------------------------------------
# 2. Credential / Secret yang ditulis langsung
#    Cari: api_key, secret, password, token yang nilainya literal
#    Skip: placeholder, env(), config(), const docblock
# ------------------------------------------------------------
scan_pattern \
    "Hard-coded Credential / Secret" \
    "(api[_-]?key|secret|password|passwd|auth_token)\s*[=:>]\s*['\"][A-Za-z0-9_\-\.]{8,}['\"]" \
    "env\(|config\(|YOUR_|REPLACE|xxx|yyy|changeme|TODO|\$[a-zA-Z]"

# ------------------------------------------------------------
# 3. Email dengan domain real (bukan @example, @test, @local)
#    Cari: email literal yang bukan placeholder
# ------------------------------------------------------------
scan_pattern \
    "Hard-coded Email (domain real)" \
    "['\"][a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}['\"]" \
    "@example\.|@test\.|@local\.|n\.widart|env\(|config\("

# ------------------------------------------------------------
# 4. Status / Role literal yang seharusnya Enum
#    Cari: perbandingan atau assignment dengan string status
#    Skip: seeder, migration (wajar pakai literal di sana)
# ------------------------------------------------------------
scan_pattern \
    "Hard-coded Status / Role (seharusnya Enum)" \
    "(\$this->|->|\[')[a-z_]*(status|role|type)['\"\]]*\s*(===?|==)\s*['\"]*(admin|user|editor|student|instructor|active|inactive|pending|approved|rejected|published|draft|archived)['\"]" \
    "Seeder|Migration|seeders|migrations"

# ------------------------------------------------------------
# 5. String status literal di where() yang diulang
#    Cari: ->where('status', 'published') style — tapi HANYA
#    yang nilainya status literal, bukan column name
# ------------------------------------------------------------
scan_pattern \
    "Hard-coded Status di Query (seharusnya Enum)" \
    "->where\(\s*['\"]status['\"]\s*,\s*['\"]*(published|draft|active|inactive|pending|archived|approved|rejected)['\"]" \
    "Seeder|Migration|seeders|migrations"

# ------------------------------------------------------------
# 6. Magic number di logic (bukan config fallback)
#    Cari: addDays, addHours, addMinutes, limit, take, skip
#    dengan angka literal
#    Skip: baris yang sudah pakai config()
# ------------------------------------------------------------
scan_pattern \
    "Magic Number di Logic" \
    "(addDays|addHours|addMinutes|addWeeks|addMonths|limit|take|skip|chunk|perPage|paginate)\s*\(\s*[0-9]{1,}" \
    "config\(|env\("

# ------------------------------------------------------------
# 7. URL di config/ yang tidak pakai env()
#    Cari: key => 'http...' tanpa env() di folder config
#    Skip: laravel default, placeholder, example
# ------------------------------------------------------------
scan_pattern \
    "URL di Config tanpa env()" \
    "['\"]\w+['\"]\s*=>\s*['\"]https?://[^'\"]{5,}['\"]" \
    "env\(|localhost|example\.com|my-default-host|your-account-id|laravel\.com|packagist\.org"

# ------------------------------------------------------------
# 8. UI / Error string yang seharusnya pakai trans() / __()
#    Cari: return atau echo string yang tampak user-facing
#    Skip: internal label, seeder output, docblock, class name
# ------------------------------------------------------------
scan_pattern \
    "Hard-coded UI / Error Message" \
    "(return|echo|abort|throw)\s+['\"][A-Z][A-Za-z\s]{4,}['\"]" \
    "env\(|trans\(|__\(|Lang::|Seeder|seeder|GenerateFilter|QueryBuilder|QueryFilter|Unknown"

# ============================================================
# HITUNG & TAMPILKAN HASIL
# ============================================================

# Hitung total file yang di-scan
for dir in $SCAN_DIRS; do
    [ -d "$dir" ] || continue
    count=$(find "$dir" -type f -name "*.$EXTENSIONS" | while read -r f; do
        skip=false
        for sd in $SKIP_DIRS; do
            [[ "$f" == *"/$sd/"* ]] && skip=true && break
        done
        [ "$skip" = false ] && echo "$f"
    done | wc -l)
    TOTAL_FILES=$((TOTAL_FILES + count))
done

TOTAL_ISSUES=$(wc -l < "$RESULT_FILE")

# --- Header ---
echo ""
echo "╔══════════════════════════════════════════════════════════════╗"
echo "║       Laravel Hard-Coded String Detector  v2                ║"
echo "╚══════════════════════════════════════════════════════════════╝"
echo ""
echo -e "  📂 Folder di-scan  : ${CYAN}${SCAN_DIRS}${NC}"
echo -e "  📄 File di-scan    : ${CYAN}${TOTAL_FILES}${NC}"
echo -e "  🔍 Total masalah   : ${RED}${BOLD}${TOTAL_ISSUES}${NC}"
echo ""

if [ "$TOTAL_ISSUES" -eq 0 ]; then
    echo -e "${GREEN}✅ Tidak ditemukan hard-coded string mencurigakan.${NC}"
    rm -f "$RESULT_FILE"
    exit 0
fi

# --- Ringkasan per kategori ---
echo "────────────────────────────────────────────────────────────────"
echo -e "${BOLD}📊 RINGKASAN PER KATEGORI${NC}"
echo "────────────────────────────────────────────────────────────────"

cut -d'|' -f1 "$RESULT_FILE" | sort | uniq -c | sort -rn | while read -r count cat; do
    printf "  %-50s ${RED}%s temuan${NC}\n" "$cat" "$count"
done

echo ""

# --- Detail per kategori ---
cut -d'|' -f1 "$RESULT_FILE" | sort -u | while IFS= read -r category; do
    echo ""
    echo "┌─────────────────────────────────────────────────────────────┐"
    printf "│ ${YELLOW}${BOLD}%-61s${NC}│\n" "$category"
    echo "└─────────────────────────────────────────────────────────────┘"

    idx=0
    grep "^${category}|" "$RESULT_FILE" | while IFS='|' read -r cat file line context; do
        idx=$((idx + 1))
        echo ""
        echo -e "  ${BOLD}[${idx}]${NC} ${CYAN}${file}${NC}  →  baris ${YELLOW}${line}${NC}"
        echo -e "      ${DIM}${context}${NC}"
    done
done

# --- Tips per kategori ---
echo ""
echo "────────────────────────────────────────────────────────────────"
echo -e "${BOLD}💡 Cara Perbaiki:${NC}"
echo ""
echo -e "  ${YELLOW}Hard-coded URL${NC}"
echo "      Pindahkan ke .env dan akses via config()"
echo "      .env:    MY_SERVICE_URL=https://api.example.com"
echo "      config:  'url' => env('MY_SERVICE_URL')"
echo ""
echo -e "  ${YELLOW}Credential / Secret${NC}"
echo "      Jangan simpan di code. Gunakan .env saja."
echo ""
echo -e "  ${YELLOW}Status / Role (Enum)${NC}"
echo "      Buat Enum di app/Enums/ dan gunakan di mana-mana"
echo "      enum Status: string { case Published = 'published'; }"
echo ""
echo -e "  ${YELLOW}Magic Number${NC}"
echo "      Pindahkan ke config/ atau gunakan named constant"
echo "      config('auth.session_idle_days', 14)"
echo ""
echo -e "  ${YELLOW}UI / Error Message${NC}"
echo "      Gunakan trans() atau __() untuk string user-facing"
echo "      return __('auth.login_failed');"
echo "────────────────────────────────────────────────────────────────"

# --- Export jika diminta ---
if [ -n "$EXPORT_FILE" ]; then
    {
        echo "Laravel Hard-Coded String Detector v2 - Laporan"
        echo "Folder: $SCAN_DIRS"
        echo "Total file: $TOTAL_FILES | Total masalah: $TOTAL_ISSUES"
        echo ""
        echo "=============================="
        cat "$RESULT_FILE" | while IFS='|' read -r cat file line context; do
            printf "[%-45s] %s baris %s\n" "$cat" "$file" "$line"
            printf "    Context: %s\n\n" "$context"
        done
    } > "$EXPORT_FILE"
    echo ""
    echo -e "${GREEN}✅ Hasil disimpan ke: ${EXPORT_FILE}${NC}"
fi

# Bersih temp file
rm -f "$RESULT_FILE"