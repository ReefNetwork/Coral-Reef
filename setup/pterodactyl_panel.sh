#
#  CCCCC                        lll RRRRRR                 fff
# CC    C  oooo  rr rr    aa aa lll RR   RR   eee    eee  ff
# CC      oo  oo rrr  r  aa aaa lll RRRRRR  ee   e ee   e ffff
# CC    C oo  oo rr     aa  aaa lll RR  RR  eeeee  eeeee  ff
#  CCCCC   oooo  rr      aaa aa lll RR   RR  eeeee  eeeee ff
#
# Copyright (c) 2021-2021. Ree-jp(https://ree-jp.net)
#
# https://gist.github.com/ree-jp/1faab49201e183021a14c7e60e89f395

for INSTALL_PLUGIN_PATH in ./install_plugins/*; do
  echo "find $INSTALL_PLUGIN_PATH"
  if [ -f $INSTALL_PLUGIN_PATH ]; then
    INSTALL_PLUGIN=${INSTALL_PLUGIN_PATH##*/}
    echo "Installing $INSTALL_PLUGIN"
    mv ./install_plugins/$INSTALL_PLUGIN ./plugins/$INSTALL_PLUGIN
  fi
done
if [ -f ./PocketMine-MP.phar ]; then
  echo "Update PocketMine-MP"
  mv server.phar old_server.phar
  mv PocketMine-MP.phar server.phar
fi
exec ./bin/php7/bin/php ./server.phar --no-wizard --disable-ansi