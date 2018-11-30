#!/bin/sh

cd ./sdk
composer install --no-dev
cd ..
rm -rf ./lightninghub.zip
mkdir ./lightninghub
cp -r ./classes ./lightninghub/
cp -r ./controllers ./lightninghub/
cp -r ./sdk ./lightninghub/
cp -r ./translations ./lightninghub/
cp -r ./upgrade ./lightninghub/
cp -r ./views ./lightninghub/
cp ./config.xml ./lightninghub/
cp ./index.php ./lightninghub/
cp ./lightninghub.php ./lightninghub/
cp ./logo.png ./lightninghub/
cp ./cron.php ./lightninghub/


zip -r lightninghub.zip lightninghub
rm -rf ./lightninghub/