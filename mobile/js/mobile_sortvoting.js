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
 * AMD module used when saving a new sort voting on mobile.
 *
 * @copyright   2024 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
this.submitResponses = () => {
    let promise;
    promise = Promise.resolve();
    promise.then(() => {
        // Show loading modal.
        return this.CoreDomUtilsProvider.showModalLoading('core.sending', true);
    }).then((modal) => {
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

        return this.sortVotingProvider.submitResponses(this.module.instance, votes).then(() => {
            // Responses have been sent to server or stored to be sent later.
            this.CoreDomUtilsProvider.showToast(this.TranslateService.instant('plugin.mod_sortvoting.votesuccess'));

            // Check completion since it could be configured to complete once the user submits a vote.
            this.CoreCourseProvider.checkModuleCompletion(this.courseId, this.module.completiondata);

            // Data has been sent, refresh the content.
            return this.refreshContent(true);
        }).catch((message) => {
            this.CoreDomUtilsProvider.showErrorModalDefault(message, 'Error submitting responses.', true);
        }).finally(() => {
            modal.dismiss();
        });
    }).catch(() => {
        // User cancelled, ignore.
    });
};

this.moveUp = (id) => {
    var options = document.getElementsByName('option[]');

    // Change value of the input elements.
    var prevId = 0;
    var canMove = true;
    options.forEach(function (option, index) {
        if (option.getAttribute('data-optionid') == id) {
            if (parseInt(option.value) == option.getAttribute('min')) {
                canMove = false;
                return;
            }
            option.value = parseInt(option.value) - 1;
            options[index - 1].value = parseInt(options[index - 1].value) + 1;
            prevId = options[index - 1].getAttribute('data-optionid');
        }
    });

    // Move elements order in the DOM.
    if (!canMove) {
        return;
    }
    var sortVotingList = document.querySelector("#sortvotinglist");
    var option = document.querySelector("#item-" + id);
    var prevOption = document.querySelector("#item-" + prevId);

    sortVotingList.insertBefore(option, prevOption);
};

this.moveDown = (id) => {
    var options = document.getElementsByName('option[]');

    // Change value of the input elements.
    var nextId = 0;
    var canMove = true;
    options.forEach(function (option, index) {
        if (option.getAttribute('data-optionid') == id) {
            if (parseInt(option.value) == option.getAttribute('max')) {
                canMove = false;
                return;
            }
            option.value = parseInt(option.value) + 1;
            options[index + 1].value = parseInt(options[index + 1].value) - 1;
            nextId = options[index + 1].getAttribute('data-optionid');
        }
    });

    // Move elements order in the DOM.
    if (!canMove) {
        return;
    }
    var sortVotingList = document.querySelector("#sortvotinglist");
    var option = document.querySelector("#item-" + id);
    var nextOption = document.querySelector("#item-" + nextId);

    sortVotingList.insertBefore(nextOption, option);
};
