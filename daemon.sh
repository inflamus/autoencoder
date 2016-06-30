#!/bin/sh
basedir=/home/romein/encoder
tmplock=/tmp/autoencoderdaemon.lock
cd $basedir

######## Encoder
if [ -e $tmplock ] ;
then	
	exit;
fi;

echo Locked > $tmplock;

for i in */encode*.sh
do
# 	sh "$i" > $i.log;
	sh "$i"
	mv "$i" "$i.end"
	cd $basedir
done;

cd $basedir
rm $tmplock


