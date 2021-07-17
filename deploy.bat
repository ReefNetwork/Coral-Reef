php -dphar.readonly=0 ./build/make-phar.php enableCompressAll
copy /y build\CoralReef.phar D:\docker\pmmp\plugins
docker run -it --rm -v D:/docker/pmmp/plugins:/plugins -v D:/docker/pmmp:/data -v D:/docker/pmmp:/pocketmine -p 19132:19132/udp pmmp/pocketmine-mp:latest
