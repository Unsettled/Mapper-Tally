# Scanner Tally
Script to check who the top scanners were between a period of time.

This script is designed for use with [Eve-Wspace](https://github.com/marbindrakon/eve-wspace/).
Thus, it will not work without it.

You can specify your own start and end dates, as well as optionally send the output to Slack.

## Setup
Copy Config.sample.php to Config.php and edit at least the `DB_*` entries.
You can also specify a Slack URL (Incoming Webhook).

## Plans
I'm planning on making it easier to lookup previous (monthly) best scanners by more categories than just systems added.
