# Vacation Portal

Please review the [Assignment](Assignment.md) document for the detailed business requirements of this assignment.

## Getting Started

1. **Environment Setup**
   - Copy the example environment file to create your own configuration:
     ```sh
     cp .env.example .env
     ```
   - Edit `.env` and update the values as needed for your local setup.

2. **Project Lifecycle Commands**
   - Use the provided `run` script to manage the project:
     - **Build the project (install dependencies, migrate, seed):**
       ```sh
       ./run build
       ```
     - **Start the project:**
       ```sh
       ./run start
       ```
     - **Stop and remove containers and volumes:**
       ```sh
       ./run teardown
       ```
     - **Run backend checks:**
       ```sh
       ./run check
       ```

You will need to first execute the `./run build` command before starting the project for the first time. Then you can
use the `./run start` command to start the project. The `./run teardown` command can be used to stop and remove all
containers and volumes (clean-up). Finally, while the project is running, you can use the `./run check` command to
run the automated checks.

3. **Manually testing the API**
   - Example login request:
     ```sh
     curl -v -X POST http://localhost:8080/auth/login \
       -H "Content-Type: application/json" \
       -d '{"email": "manager@example.com", "password": "managerpass123"}'
     ```
   - Alternatively you can use the `./test_routes.sh` script inside the `be` folder to test all the API routes.

Refer to the `run` script for more details on each command.
