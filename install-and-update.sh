#/bin/bash
CMD_PHP=/usr/bin/php
CMD_MYSQL=/usr/bin/mysql
 
# make sure PHP and MySQL binary exist; else die with an error message
type -P $CMD_PHP &>/dev/null || { echo "$CMD_PHP not found. Set \$CMD_PHP in $0"; exit 1; }
type -P $CMD_MYSQL &>/dev/null || { echo "$CMD_MYSQL not found. Set \$CMD_MYSQL in $0"; exit 1; }

case "$1" in
"")         CONFIG=""
            ;;
"--check")  CONFIG=",'updateCheck' => '1'"
            ;;
"--database")  CONFIG=",'dbOnly' => '1'"
            ;;
"--help")  CONFIG=",'help' => '1'"
            ;;
"--maintenance")  
            MODE=${2:-show};
            CONFIG=",'maintenance' => '$MODE'"
            ;;
*)          CONFIG=",'applicationZipOverride' => '$1'"
            ;;
esac

$CMD_PHP -r "require_once('application/modules/default/Models/Installer/Standalone.php'); Models_Installer_Standalone::mainLinux(array('mysql_bin' => '$CMD_MYSQL'${CONFIG}));"
