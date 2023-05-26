# SortVoting #

This plugin allows sorting a list of options by preference of the user, instead of just selecting one.

The problem with the ordinary voting (selecting A or B) is that if many people hate the winning option but the majority votes for it, it will win. This plugin will allow users to set in order the preference of multiple options.

## Examples: ##
**Setup:**
- Available options A, B, C, D
- People voting 5

### Traditional voting: ###

**Votes:**

- User1 = A
- User2 = A
- User3 = B
- User4 = C
- User5 = D

**Results in traditional voting:**

- A = 2
- B = 1
- C = 1
- D = 1

**Explanation**

A gets 2 votes from user1 and user2 and all the other options get one vote each. Users 3, 4 and 5 might prefer to have the option B before having the option A, but since the majority voted for A, A will be the winner.

### Voting with the plugin: ###

**Votes:**

- User1:
    - A
    - B
    - C
    - D
- User2:
    - A
    - B
    - D
    - C
- User3:
    - B
    - D
    - C
    - A
- User4:
    - C
    - B
    - D
    - A
- User5:
    - D
    - B
    - C
    - A

**Results voting with the plugin:**

- B
- D
- C
- A

**Explanation**

If we calculate the average position of the options in the votes the winner would be B.



## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/mod/sortvoting

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## License ##

2023 Odei Alba <odeialba@odeialba.com>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
