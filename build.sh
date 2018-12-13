#!/bin/sh

cd ./sdk
composer install --no-dev
cd ..
rm -rf ./peach-commerce.zip
mkdir ./peachcommerce
cp -r ./classes ./peachcommerce/
cp -r ./controllers ./peachcommerce/
cp -r ./sdk ./peachcommerce/
cp -r ./translations ./peachcommerce/
cp -r ./views ./peachcommerce/
cp ./config.xml ./peachcommerce/
cp ./index.php ./peachcommerce/
cp ./peachcommerce.php ./peachcommerce/
cp ./logo.png ./peachcommerce/
cp ./cron.php ./peachcommerce/


zip -r peach-commerce.zip peachcommerce
rm -rf ./peachcommerce/