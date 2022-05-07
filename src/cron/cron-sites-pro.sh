#!/bin/bash
#https://gist.github.com/zeroasterisk/7948457

ENV='prod'
ROOT="/var/www/${ENV}/"

SITES=( ao sp ot pt )

# foreach site
for i in "${SITES[@]}"
do

  # if the site's directory exists
  echo "checking: ${ROOT}${i}/app"
  if [[ -d "${ROOT}${i}/app" ]]; then

    # cd into it, and run cron
    cd "${ROOT}${i}/app"
    echo "  running ./cake cron run >> /var/log/crons/sp_cron.log"
    #./cake cron run >> /var/log/crons/sp_cron.log
  else
    echo "  skipping"
  fi
done