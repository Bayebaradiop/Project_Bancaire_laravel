#!/bin/bash

# Script de test Swagger après déploiement
# Usage: ./test_swagger.sh

BASE_URL="https://project-bancaire-laravel.onrender.com"

echo "======================================"
echo "Test Swagger Documentation Deployment"
echo "======================================"
echo ""

# Test 1: Check if JSON documentation is accessible
echo "1️⃣  Testing JSON documentation endpoint..."
HTTP_CODE=$(curl -s -o /tmp/swagger_json.json -w "%{http_code}" "${BASE_URL}/docs")
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ JSON docs accessible (HTTP $HTTP_CODE)"
    echo "   Preview: $(head -c 200 /tmp/swagger_json.json)..."
else
    echo "❌ JSON docs failed (HTTP $HTTP_CODE)"
fi
echo ""

# Test 2: Check if Swagger UI is accessible
echo "2️⃣  Testing Swagger UI endpoint..."
HTTP_CODE=$(curl -s -o /tmp/swagger_ui.html -w "%{http_code}" "${BASE_URL}/api/documentation")
if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ Swagger UI accessible (HTTP $HTTP_CODE)"
    if grep -q "swagger-ui" /tmp/swagger_ui.html; then
        echo "   ✅ Swagger UI HTML found"
    else
        echo "   ⚠️  HTML returned but no swagger-ui detected"
    fi
else
    echo "❌ Swagger UI failed (HTTP $HTTP_CODE)"
    echo "   Response preview:"
    head -n 20 /tmp/swagger_ui.html
fi
echo ""

# Test 3: Test CORS headers
echo "3️⃣  Testing CORS headers..."
CORS_HEADERS=$(curl -s -I -X OPTIONS "${BASE_URL}/api/documentation" | grep -i "access-control")
if [ -n "$CORS_HEADERS" ]; then
    echo "✅ CORS headers present:"
    echo "$CORS_HEADERS" | sed 's/^/   /'
else
    echo "⚠️  No CORS headers detected"
fi
echo ""

# Test 4: Test API endpoints
echo "4️⃣  Testing sample API endpoint..."
HTTP_CODE=$(curl -s -o /tmp/api_test.json -w "%{http_code}" "${BASE_URL}/api/comptes")
if [ "$HTTP_CODE" = "200" ] || [ "$HTTP_CODE" = "401" ]; then
    echo "✅ API endpoint responsive (HTTP $HTTP_CODE)"
else
    echo "❌ API endpoint failed (HTTP $HTTP_CODE)"
fi
echo ""

# Summary
echo "======================================"
echo "Test Summary"
echo "======================================"
echo "Base URL: $BASE_URL"
echo "Documentation URL: ${BASE_URL}/api/documentation"
echo "JSON API Spec: ${BASE_URL}/docs"
echo ""
echo "Pour voir la documentation complète, ouvrez:"
echo "👉 ${BASE_URL}/api/documentation"
echo ""

# Cleanup
rm -f /tmp/swagger_*.* /tmp/api_test.json
