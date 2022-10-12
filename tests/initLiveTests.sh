#!/bin/bash

if [ -d livetests ]; then
  rm -rf livetests
fi
mkdir livetests
cp -a assets/* livetests/
echo "you can now run Composer in livetests/app1 or livetests/app2"


