#!/bin/bash

# Base URL
BASE_URL="http://localhost:8080"

# Credentials
MANAGER_EMAIL="manager@example.com"
MANAGER_PASSWORD="managerpass123"
EMPLOYEE_EMAIL="employee@example.com"
EMPLOYEE_PASSWORD="employeepass123"

# Tokens
MANAGER_TOKEN=""
EMPLOYEE_TOKEN=""

# Function to log in and get a token
login() {
    EMAIL=$1
    PASSWORD=$2
    RESPONSE=$(curl -s -X POST -H "Content-Type: application/json" -d "{\"email\":\"$EMAIL\",\"password\":\"$PASSWORD\"}" $BASE_URL/auth/login)
    TOKEN=$(echo $RESPONSE | jq -r .token)
    echo $TOKEN
}

# Get tokens
echo "Getting tokens..."
MANAGER_TOKEN=$(login $MANAGER_EMAIL $MANAGER_PASSWORD)
EMPLOYEE_TOKEN=$(login $EMPLOYEE_EMAIL $EMPLOYEE_PASSWORD)
echo "Manager Token: $MANAGER_TOKEN"
echo "Employee Token: $EMPLOYEE_TOKEN"


# --- Users ---
echo -e "\n--- Testing Users Routes (as Manager) ---"

# Create a new user and capture the ID
echo -e "\nCreating a new user..."
CREATE_USER_RESPONSE=$(curl -s -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $MANAGER_TOKEN" -d '{"email":"newuser@example.com","password":"newpassword123","name":"New User","employeeCode":"1000003","role":"employee"}' $BASE_URL/users)
NEW_USER_ID=$(echo $CREATE_USER_RESPONSE | jq -r .id)
echo "$CREATE_USER_RESPONSE"
echo "New User ID: $NEW_USER_ID"

# Get all users
echo -e "\nGetting all users..."
curl -X GET -H "Authorization: Bearer $MANAGER_TOKEN" $BASE_URL/users

# Get the newly created user
echo -e "\nGetting user with ID $NEW_USER_ID..."
curl -X GET -H "Authorization: Bearer $MANAGER_TOKEN" $BASE_URL/users/$NEW_USER_ID

# Update the newly created user
echo -e "\nUpdating user with ID $NEW_USER_ID..."
curl -X PUT -H "Content-Type: application/json" -H "Authorization: Bearer $MANAGER_TOKEN" -d '{"name":"Updated User Name"}' $BASE_URL/users/$NEW_USER_ID

# Delete the newly created user
echo -e "\nDeleting user with ID $NEW_USER_ID..."
curl -X DELETE -H "Authorization: Bearer $MANAGER_TOKEN" $BASE_URL/users/$NEW_USER_ID


# --- Vacations (as Employee) ---
echo -e "\n--- Testing Vacations Routes (as Employee) ---"

# Create a vacation request and capture the ID
echo -e "\nCreating a vacation request..."
CREATE_VACATION_RESPONSE=$(curl -s -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $EMPLOYEE_TOKEN" -d '{"startDate":"2525-12-20","endDate":"2525-12-25","reason":"Time to rest"}' $BASE_URL/vacations)
NEW_VACATION_ID=$(echo $CREATE_VACATION_RESPONSE | jq -r .id)
echo "$CREATE_VACATION_RESPONSE"
echo "New Vacation ID: $NEW_VACATION_ID"

# Get all vacation requests (own)
echo -e "\nGetting all vacation requests for employee..."
curl -X GET -H "Authorization: Bearer $EMPLOYEE_TOKEN" $BASE_URL/vacations

# Delete the newly created vacation request
echo -e "\nDeleting vacation request with ID $NEW_VACATION_ID..."
curl -X DELETE -H "Authorization: Bearer $EMPLOYEE_TOKEN" $BASE_URL/vacations/$NEW_VACATION_ID


# --- Vacations (as Manager) ---
echo -e "\n--- Testing Vacations Routes (as Manager) ---"

echo -e "\nCreating a vacation request (for approve)..."
CREATE_VACATION_RESPONSE=$(curl -s -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $EMPLOYEE_TOKEN" -d '{"startDate":"2525-12-20","endDate":"2525-12-25","reason":"Time to rest"}' $BASE_URL/vacations)
NEW_VACATION_ID1=$(echo $CREATE_VACATION_RESPONSE | jq -r .id)
echo "New Vacation ID: $NEW_VACATION_ID1"

echo -e "\nCreating a vacation request (for reject)..."
CREATE_VACATION_RESPONSE=$(curl -s -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $EMPLOYEE_TOKEN" -d '{"startDate":"2525-12-20","endDate":"2525-12-25","reason":"Time to rest"}' $BASE_URL/vacations)
NEW_VACATION_ID2=$(echo $CREATE_VACATION_RESPONSE | jq -r .id)
echo "New Vacation ID: $NEW_VACATION_ID2"

# Get all vacation requests
echo -e "\nGetting all vacation requests for manager..."
curl -X GET -H "Authorization: Bearer $MANAGER_TOKEN" $BASE_URL/vacations

# Approve a vacation request (ID 1)
echo -e "\nApproving vacation request with ID $NEW_VACATION_ID1..."
curl -X PUT -H "Authorization: Bearer $MANAGER_TOKEN" $BASE_URL/vacations/$NEW_VACATION_ID1/approve

# Reject a vacation request (ID 1)
echo -e "\nRejecting vacation request with ID $NEW_VACATION_ID2..."
curl -X PUT -H "Authorization: Bearer $MANAGER_TOKEN" $BASE_URL/vacations/$NEW_VACATION_ID2/reject


# --- Authentication ---
echo -e "\n--- Testing Authentication Routes ---"

# Renew a token
echo -e "\nRenewing a token..."
curl -X POST -H "Content-Type: application/json" -H "Authorization: Bearer $MANAGER_TOKEN" $BASE_URL/auth/renew
