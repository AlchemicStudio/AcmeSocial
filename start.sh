#!/usr/bin/env bash
cd api
cp .env.local .env
composer install
touch database/database.sqlite
cd ../frontend
npm install
