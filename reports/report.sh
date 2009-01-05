#!/bin/sh
when=`date +%F -d "yesterday"`
cat /var/log/httpd/access_log.1 /var/log/httpd/access_log | /home/njl/src/qdos/trunk/reports/httplogs.pl $when final
mail -s "QDOS brief daily report for $when" staff@garlik.com njl@tlrmx.org -- -f nick.lamb@garlik.com < /tmp/brief_daily
export EMAIL=nick.lamb@garlik.com
mutt -s "QDOS excel report for $when" -a /tmp/qdossearch.xls jayne.sankoh-beacom@garlik.com nick.lamb@garlik.com Lyndsey@agenda21digital.com christian.davis@garlik.com iain.farquharson@garlik.com terry.leonard@garlik.com cottie.petrie-norris@garlik.com steve.harris@garlik.com < /dev/null
