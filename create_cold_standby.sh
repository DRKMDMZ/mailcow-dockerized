#!/bin/bash

export REMOTE_SSH_KEY=/root/.ssh/id_ed25519
export REMOTE_SSH_PORT=22
export REMOTE_SSH_HOST=103.241.50.89

/opt/mailcow-dockerized/helper-scripts/_cold-standby.sh
