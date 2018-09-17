#!/bin/sh

cd ./sdk
composer install --no-dev
cd ..
rm -rf ./LightningHub.zip
mkdir ./LightningHub
cp -r ./classes ./LightningHub/
cp -r ./controllers ./LightningHub/
cp -r ./sdk ./LightningHub/
cp -r ./translations ./LightningHub/
cp -r ./upgrade ./LightningHub/
cp -r ./views ./LightningHub/
cp ./config.xml ./LightningHub/
cp ./index.php ./LightningHub/
cp ./LightningHub.php ./LightningHub/
cp ./logo.png ./LightningHub/


zip -r LightningHub.zip LightningHub
rm -rf ./LightningHub/