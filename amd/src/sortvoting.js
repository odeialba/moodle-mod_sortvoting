// This file is part of the mod_sortvoting plugin for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AMD module used when saving a new sort voting.
 *
 * @module      mod_sortvoting/issues-list
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Notification from 'core/notification';
import Ajax from 'core/ajax';
import SortableList from 'core/sortable_list';
import {add as toastAdd} from 'core/toast';
import jQuery from 'jquery';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    SAVEVOTE: "[data-action='savevote']"
};

/**
 * Save sort vote.
 * @param {Element} saveSortVoteElement
 */
const saveVote = function(saveSortVoteElement) {
    saveSortVoteElement.setAttribute('disabled', true);
    var sortvotingid = document.getElementsByName('sortvotingid')[0].value;
    var options = document.getElementsByName('option[]');

    // Build votes and positions arrays for later processing.
    var votes = [];
    var positions = [];
    options.forEach(function(option) {
        positions.push(option.value);
        votes.push({
            'position': option.value,
            'optionid': option.getAttribute('data-optionid')
        });
    });

    // Check if all elements of the positions array are unique.
    if (new Set(positions).size !== positions.length) {
        window.alert(getString('errorduplicatedposition', 'mod_sortvoting'));
        saveSortVoteElement.removeAttribute('disabled');
        return;
    }

    // Save vote.
    var promises = Ajax.call([
        {methodname: 'mod_sortvoting_save_vote', args: {sortvotingid: sortvotingid, votes: votes}}
    ]);
    promises[0].done(function(result) {
        if (result.success) {
            toastAdd(getString('votesuccess', 'mod_sortvoting'), {type: 'success'});
        } else {
            toastAdd(getString('voteerror', 'mod_sortvoting'), {type: 'danger'});
        }
        if (result.allowupdate) {
            saveSortVoteElement.removeAttribute('disabled');
        } else {
            saveSortVoteElement.style.display = 'none';
            jQuery('ul#sortvotinglist > li.optionitem').each(function(index, element) {
                element.classList.remove('optionitem', 'draggable');
                element.classList.add('bg-gray-100');
                element.removeAttribute('draggable');
                element.removeAttribute('data-drag-type');
            });
        }
    }).fail(Notification.exception);
};

/**
 * Sets up sortable list in the column sort order page.
 */
const setupSortableLists = () => {
    new SortableList('#sortvotinglist', {
        moveHandlerSelector: '.optionitem',
    });
    // Listen to the events when element is dragged.
    jQuery('ul#sortvotinglist > *').on(SortableList.EVENTS.DROP, function(evt, info) {
        if (info.positionChanged) {
            // Get the ul element and loop into the li elements.
            var list = info.sourceList[0];
            var lis = list.getElementsByTagName('li');
            for (var i = 0; i < lis.length; i++) {
                // Set the value of the input to the position of the li element.
                lis[i].getElementsByTagName('input')[0].value = i + 1;
            }
        }
    });
};

/**
 * Init page
 */
export function init() {
    setupSortableLists();
    document.addEventListener('click', event => {

        // Save sort vote.
        const saveSortVoteElement = event.target.closest(SELECTORS.SAVEVOTE);
        if (saveSortVoteElement) {
            event.preventDefault();
            saveVote(saveSortVoteElement);
        }
    });
}
