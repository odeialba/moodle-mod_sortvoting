<?php
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_sortvoting\output;
use html_writer;
use moodle_url;

/**
 * Renderer class.
 *
 * @package     mod_sortvoting
 * @copyright   2023 Odei Alba <odeialba@odeialba.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {
    /**
     * Returns HTML to display choices of option
     * @param array $options
     * @param int  $coursemoduleid
     * @param bool $vertical
     * @return string
     */
    public function display_options2($options, $coursemoduleid, $vertical = false, $multiple = false) {
        // $layoutclass = 'horizontal';
        // if ($vertical) {
        $layoutclass = 'vertical';
        // }
        $target = new moodle_url('/mod/sortvoting/view.php');
        $attributes = array('method'=>'POST', 'action'=>$target, 'class'=> $layoutclass);
        $disabled = empty($options['previewonly']) ? [] : array('disabled' => 'disabled');

        $html = html_writer::start_tag('form', $attributes);
        $html .= html_writer::start_tag('ul', array('class' => 'choices list-unstyled unstyled'));

        $availableoption = count($options['options']);
        $choicecount = 0;
        foreach ($options['options'] as $option) {
            $choicecount++;
            $html .= html_writer::start_tag('li', array('class' => 'option mr-3'));
            if ($multiple) {
                $option->attributes->name = 'answer[]';
                $option->attributes->type = 'checkbox';
            } else {
                $option->attributes->name = 'answer';
                $option->attributes->type = 'radio';
            }
            $option->attributes->id = 'choice_'.$choicecount;
            $option->attributes->class = 'mx-1';

            $labeltext = $option->text;
            if (!empty($option->attributes->disabled)) {
                $labeltext .= ' ' . get_string('full', 'choice');
                $availableoption--;
            }

            if (!empty($options['limitanswers']) && !empty($options['showavailable'])) {
                $labeltext .= html_writer::empty_tag('br');
                $labeltext .= get_string("responsesa", "choice", $option->countanswers);
                $labeltext .= html_writer::empty_tag('br');
                $labeltext .= get_string("limita", "choice", $option->maxanswers);
            }

            $html .= html_writer::empty_tag('input', (array)$option->attributes + $disabled);
            $html .= html_writer::tag('label', $labeltext, array('for'=>$option->attributes->id));
            $html .= html_writer::end_tag('li');
        }
        $html .= html_writer::tag('li','', array('class'=>'clearfloat'));
        $html .= html_writer::end_tag('ul');
        $html .= html_writer::tag('div', '', array('class'=>'clearfloat'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'sesskey', 'value'=>sesskey()));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'action', 'value'=>'makechoice'));
        $html .= html_writer::empty_tag('input', array('type'=>'hidden', 'name'=>'id', 'value'=>$coursemoduleid));

        if (empty($options['previewonly'])) {
            if (!empty($options['hascapability']) && ($options['hascapability'])) {
                if ($availableoption < 1) {
                    $html .= html_writer::tag('label', get_string('choicefull', 'choice'));
                } else {
                    $html .= html_writer::empty_tag('input', array(
                        'type' => 'submit',
                        'value' => get_string('savemychoice', 'choice'),
                        'class' => 'btn btn-primary'
                    ));
                }

                if (!empty($options['allowupdate']) && ($options['allowupdate'])) {
                    $url = new moodle_url('view.php',
                            array('id' => $coursemoduleid, 'action' => 'delchoice', 'sesskey' => sesskey()));
                    $html .= html_writer::link($url, get_string('removemychoice', 'choice'), array('class' => 'ml-1'));
                }
            } else {
                $html .= html_writer::tag('label', get_string('havetologin', 'choice'));
            }
        }

        $html .= html_writer::end_tag('ul');
        $html .= html_writer::end_tag('form');

        return $html;
    }
}
