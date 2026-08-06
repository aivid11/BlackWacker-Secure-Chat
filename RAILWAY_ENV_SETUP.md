# Railway environment setup

Set the following environment variables in the Railway project dashboard (Project → Variables):

- MYSQL_DATABASE = railway
- MYSQL_ROOT_PASSWORD = awdagjuNXDqEipVEgncTnarPiSDiOyBp
- MYSQLUSER = root
- MYSQLPORT = 3306
- RAILWAY_PRIVATE_DOMAIN = <your-railway-db-host>

Do NOT store these secrets in the repository. After adding them to Railway, remove the .env file from the repo and keep .env.example instead.
