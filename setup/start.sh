#
#
#  ____            _        _   __  __ _                  __  __ ____
# |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
# | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
# |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
# |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU Lesser General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# @author PocketMine Team
# @link http://www.pocketmine.net/
# @source https://github.com/pmmp/PocketMine-Docker/blob/master/pocketmine-mp/start.sh

set -e

# Detect unowned files (this is a common docker issue)
BAD_COUNT=$(find /data /plugins ! -user pocketmine | wc -l)
if [ $BAD_COUNT -gt 0 ]; then
	echo "=== WARNING ==="
	echo "Detected $BAD_COUNT files in /data or /plugins not owned by the user \"pocketmine\"!"
	echo "For example:"
	find /data /plugins ! -user pocketmine | head
	echo "This may cause problems when running the server."
	echo "If you mount /data and /plugins from a local directory, consider running \`chown -R 1000:1000 \$MOUNT\` (replace \$MOUNT with your local directory) (you may need sudo to run this)"
	echo "==============="
	# Continue running the server since this is not necessarily fatal
fi

# Install plugins from separated-separated env var $POCKETMINE_PLUGINS
# Versions can be appended to plugin name separated by a colon
# Example: POCKETMINE_PLUGINS="EconomyAPI:5.7.2 PurePerms PureChat:1.4.11"
if [ ! -z "$POCKETMINE_PLUGINS" ]; then
	for PLUGIN in $(echo $POCKETMINE_PLUGINS | tr " " "\n"); do
		PLUGIN_NAME=$(echo $PLUGIN:: | cut -d: -f1)
		PLUGIN_VERSION=$(echo $PLUGIN:: | cut -d: -f2) # The trailing :: ensures that `cut` won't take the first field as the second field

		if [ ! -f /plugins/$PLUGIN_NAME.phar ]; then
			echo "Installing $PLUGIN_NAME:$VERSION from Poggit"
			wget -O /plugins/$PLUGIN_NAME.phar https://poggit.pmmp.io/get/$PLUGIN_NAME/$PLUGIN_VERSION
		fi
	done
fi

for INSTALL_PLUGIN in /data/install_plugins/*; do
  echo "find $INSTALL_PLUGIN"
  if [ -f /data/install_plugins/$INSTALL_PLUGIN ]; then
    echo "Installing $INSTALL_PLUGIN"
    mv /data/install_plugins/$INSTALL_PLUGIN /plugins/$INSTALL_PLUGIN
  fi
done

# Run the server
cd /pocketmine
exec php PocketMine-MP.phar --no-wizard --enable-ansi --data=/data --plugins=/plugins
