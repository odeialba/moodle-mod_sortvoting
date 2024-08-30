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

const context = this;

/**
 * Class to handle sort votings.
 */
class AddonModSortVotingProvider {
    /**
     * Send the responses to a sort voting.
     *
     * @param sortvotingid Sort voting ID to submit.
     * @param votes The responses to send.
     * @param siteId Site ID. If not defined, current site.
     * @return Promise resolved with boolean: true if responses sent to server, false and rejected if failure.
     */
    submitResponses(sortvotingid, votes, siteId) {
        siteId = siteId || context.CoreSitesProvider.getCurrentSiteId();

        // TODO: Add offline option.
        // Now try to delete the responses in the server.
        return this.submitResponsesOnline(sortvotingid, votes, siteId).then(() => {
            return true;
        }).catch((error) => {
            // The WebService has thrown an error, this means that responses cannot be submitted.
            throw error;
        });
    }

    /**
     * Send responses from a sort voting to Moodle. It will fail if offline or cannot connect.
     *
     * @param sortvotingid Sort voting ID to submit.
     * @param votes The responses to send.
     * @param siteId Site ID. If not defined, current site.
     * @return Promise resolved if deleted, rejected if failure.
     */
    submitResponsesOnline(sortvotingid, votes, siteId) {
        return context.CoreSitesProvider.getSite(siteId).then((site) => {
            var params = {
                sortvotingid: sortvotingid,
                votes: votes
            };

            /* eslint-disable promise/no-nesting */
            return site.write('mod_sortvoting_save_vote', params).then((response) => {
                if (!response || response.success === false) {
                    // TODO: Add warnings array to save_vote returns.
                    // Couldn't save the responses. Reject the promise.
                    var error = response && response.warnings && response.warnings[0] ?
                            response.warnings[0] : new context.CoreError('');

                    throw error;
                }

                return;
            });
            /* eslint-enable promise/no-nesting */
        });
    }

}

const sortVotingProvider = new AddonModSortVotingProvider();

const result = {
    sortVotingProvider: sortVotingProvider,
};

// eslint-disable-next-line no-unused-expressions
result;