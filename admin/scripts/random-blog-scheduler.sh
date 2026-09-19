#!/bin/bash
# Random Time Blog Generator
# Generates blogs at random times throughout the day

# Generate random hour (0-23)
RANDOM_HOUR=$((RANDOM % 24))

# Generate random minute (0-59)
RANDOM_MINUTE=$((RANDOM % 60))

# Log the scheduled time
echo "$(date): Scheduling blog generation for $(printf '%02d:%02d' $RANDOM_HOUR $RANDOM_MINUTE)" >> /var/log/blog-scheduler.log

# Schedule the blog generation using 'at' command
echo "curl -s https://www.gcmsafetynets.in/admin/api/auto-blog-generator.php?generate=1 >> /var/log/blog-generation.log 2>&1" | at $(printf '%02d:%02d' $RANDOM_HOUR $RANDOM_MINUTE)

# Alternative: Run immediately with random delay (0-86400 seconds = 24 hours)
# RANDOM_DELAY=$((RANDOM % 86400))
# sleep $RANDOM_DELAY
# curl -s https://www.gcmsafetynets.in/admin/api/auto-blog-generator.php?generate=1
