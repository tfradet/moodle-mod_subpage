<?php
// This file is part of Moodle - http://moodle.org/
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
 * Moodle subpage Renderer
 *
 * @package mod
 * @subpackage subpage
 * @copyright 2011 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author Dan Marsden <dan@danmarsden.com>
 */

defined('MOODLE_INTERNAL') || die();

class mod_subpage_renderer extends plugin_renderer_base {
    protected $subpagecm;

    /**
     * Render contents of main part of subpage
     * @param mod_subpage $subpage
     * @param course_modinfo $modinfo
     * @param array $sections
     * @param boolean $editing whether the user is allowed to edit this page
     * @param int $moveitem (currently not used)
     * @param boolean $movesection whether the user is allowed to move sections
     * @return string html for display
     */
    public function render_subpage($subpage, $modinfo, $sections, $editing,
            $moveitem, $movesection) {
        global $PAGE, $OUTPUT, $CFG, $USER;
        $this->subpagecm = $subpage->get_course_module()->id;
        if (!empty($USER->activitycopy) && $movesection) {
            $content = $this->render_cancel_link($this->subpagecm);
        } else {
            $content = '';
        }
        $content .= $this->render_intro($subpage);
        $streditsummary  = get_string('editsummary');
        $strdelete = get_string('delete');

        if ($editing) {
            $strmoveup   = get_string('moveup');
            $strmovedown = get_string('movedown');
            $strhide  = get_string('hide');
            $strshow = get_string('show');
            $strstealth = get_string('stealth', 'subpage');
            $strunstealth = get_string('unstealth', 'subpage');
        }
        $coursecontext = get_context_instance(CONTEXT_COURSE, $subpage->get_course()->id);

        $modinfo = get_fast_modinfo($subpage->get_course()->id);
        $mods = $modinfo->get_cms();
        $modnames = get_module_types_names();
        $modnamesplural = get_module_types_names(true);
        $modnamesused = $modinfo->get_used_module_names();
        foreach ($mods as $modid => $unused) {
            if (!isset($modinfo->cms[$modid])) {
                rebuild_course_cache($subpage->get_course()->id);
                $modinfo = get_fast_modinfo($subpage->get_course());
                debugging('Rebuilding course cache', DEBUG_DEVELOPER);
                break;
            }
        }
        $lastpageorder = $subpage->get_last_section_pageorder();
        if ($subpage->get_course()->format == 'weeks') {
            $content .= html_writer::start_tag('ul', array('class' => 'weeks'));
        } else {
            $content .= html_writer::start_tag('ul', array('class' => 'topics'));
        }

        foreach ($sections as $section) {
            // Check to see whether cms within the section are visible or not
            // If all cms are not visible then we don't show the section at all,
            // unless editing
            $visible = false;
            if ($section->sequence) {
                // get cm_info for this resources
                $instances = explode(',', $section->sequence);
            } else {
                $instances = array();
            }
            foreach ($instances as $instance) {
                $cm = $modinfo->get_cm($instance);
                // check to see whether cm is visible
                if ($cm->uservisible) {
                    $visible = true;
                    break;
                }
            }
            // If section is empty so should be hidden, record that in object
            $section->autohide = !$visible;

            $content .= html_writer::start_tag('li',
                    array('class' => 'section main clearfix', 'id'=>'section-'.$section->section));
            $content .= html_writer::tag('div', '&nbsp;', array('class' => 'left side'));
            $content .= html_writer::start_tag('div', array('class' => 'right side'));
            if ($editing && has_capability('moodle/course:update', $coursecontext)) {
                // Show the hide/show eye
                if ($section->visible) {
                    $content .= html_writer::start_tag('a', array(
                            'href' => 'view.php?id=' . $subpage->get_course_module()->id .
                                '&hide=' . $section->section . '&sesskey=' . sesskey() .
                                '#section-'.$section->id,
                            'title'=> $strhide));
                    $content .= html_writer::empty_tag('img', array(
                            'src' => $OUTPUT->pix_url('i/hide'), 'class'=>'icon hide',
                            'alt'=>$strhide));
                    $content .= html_writer::end_tag('a');
                } else {
                    $content .= html_writer::start_tag('a', array(
                            'href' => 'view.php?id=' . $subpage->get_course_module()->id .
                                '&show=' . $section->section . '&sesskey=' . sesskey() .
                                '#section-' . $section->id,
                            'title'=> $strshow));
                    $content .= html_writer::empty_tag('img', array(
                            'src' => $OUTPUT->pix_url('i/show'), 'class'=>'icon show',
                            'alt'=>$strshow));
                    $content .= html_writer::end_tag('a');
                }

                // Show the stealth/unstealth section link
                if ($section->stealth) {
                    $content .= html_writer::start_tag('form',
                            array('method' => 'post', 'action' => 'stealth.php'));
                    $content .= html_writer::start_tag('div');
                    $content .= html_writer::empty_tag('input',
                            array('name' => 'id', 'value' => $subpage->get_course_module()->id,
                            'type' => 'hidden'));
                    $content .= html_writer::empty_tag('input',
                            array('name' => 'sesskey', 'value' => sesskey(), 'type' => 'hidden'));
                    $content .= html_writer::empty_tag('input',
                            array('name' => 'unstealth', 'value' => $section->id,
                            'type' => 'hidden'));
                    $content .= html_writer::empty_tag('input',
                            array('name' => 'icon',
                            'src' => $OUTPUT->pix_url('unstealth', 'mod_subpage'),
                            'type' => 'image', 'title' => $strunstealth, 'alt' => $strunstealth));
                    $content .= html_writer::end_tag('div');
                    $content .= html_writer::end_tag('form');
                } else {
                    $content .= html_writer::start_tag('form',
                            array('method' => 'post', 'action' => 'stealth.php'));
                    $content .= html_writer::start_tag('div');
                    $content .= html_writer::empty_tag('input',
                            array('name' => 'id', 'value' => $subpage->get_course_module()->id,
                            'type' => 'hidden'));
                    $content .= html_writer::empty_tag('input',
                            array('name' => 'sesskey', 'value' => sesskey(), 'type' => 'hidden'));
                    $content .= html_writer::empty_tag('input',
                            array('name' => 'stealth', 'value' => $section->id,
                            'type' => 'hidden'));
                    $content .= html_writer::empty_tag('input',
                            array('name' => 'icon',
                            'src' => $OUTPUT->pix_url('stealth', 'mod_subpage'),
                            'type' => 'image', 'title' => $strstealth, 'alt' => $strstealth));
                    $content .= html_writer::end_tag('div');
                    $content .= html_writer::end_tag('form');
                }
                $content .= html_writer::empty_tag('br', array());

                if ($movesection) {
                    $content .= html_writer::start_tag('span', array(
                            'class' => 'section_move_commands'));
                    if ($section->pageorder > 1) {
                        // Add a arrow to move section up
                        $content .= html_writer::start_tag('a', array(
                                'href' => 'view.php?id=' . $subpage->get_course_module()->id .
                                    '&random=' . rand(1, 10000) . '&section=' . $section->id .
                                    '&move=-1&sesskey=' . sesskey() .
                                    '#section-' . ($section->id-1),
                                'title'=> $strmoveup));
                        $content .= html_writer::empty_tag('img', array(
                                'src' => $OUTPUT->pix_url('t/up'), 'class'=>'icon up',
                                'alt'=>$strmoveup));
                        $content .= html_writer::end_tag('a');
                        $content .= html_writer::empty_tag('br', array());
                    }

                    if ($section->pageorder < $lastpageorder) {
                        // Add an arrow to move section down
                        $content .= html_writer::start_tag('a', array(
                                'href' => 'view.php?id=' . $subpage->get_course_module()->id .
                                    '&random=' . rand(1, 10000) . '&section=' . $section->id .
                                    '&move=1&sesskey=' . sesskey() . '#section-'.($section->id+1),
                                'title'=> $strmovedown));
                        $content .= html_writer::empty_tag('img', array(
                                'src' => $OUTPUT->pix_url('t/down'), 'class'=>'icon down',
                                'alt'=>$strmovedown));
                        $content .= html_writer::end_tag('a');
                        $content .= html_writer::empty_tag('br', array());
                    }
                    $content .= html_writer::end_tag('span');
                }
            }
            $content .= html_writer::end_tag('div');

            $content .= html_writer::start_tag('div', array('class' => 'content'));
            // Only show the section if visible and not stealthed or to users with permission
            if ((($section->visible && !$section->stealth) ||
                    has_capability('moodle/course:viewhiddensections', $coursecontext)) &&
                    ($editing || !$section->autohide)) {
                if ($section->stealth) {
                    $content .= html_writer::start_tag('div', array('class' => 'stealthed'));
                }
                if (!empty($section->name)) {
                    $content .= html_writer::tag('h3', format_string($section->name),
                            array('class' => 'sectionname'));
                }
                $summary = '';
                if ($section->summary) {
                    $summarytext = file_rewrite_pluginfile_urls($section->summary,
                            'pluginfile.php', $coursecontext->id, 'course',
                            'section', $section->id);
                    $summaryformatoptions = new stdClass();
                    $summaryformatoptions->noclean = true;
                    $summaryformatoptions->overflowdiv = true;
                    $summary .= format_text($summarytext, $section->summaryformat,
                            $summaryformatoptions);
                }
                if ($editing && has_capability('moodle/course:update', $coursecontext)) {
                    $summary .= html_writer::start_tag('a', array(
                            'href' => $CFG->wwwroot . '/course/editsection.php?id=' .
                                $section->id . '&returnurl=' . urlencode($CFG->wwwroot .
                                '/mod/subpage/view.php?id=' . $subpage->get_course_module()->id .
                                '&recache=1&sesskey=' . sesskey()),
                            'title'=> $streditsummary));
                    $summary .= html_writer::empty_tag('img', array(
                            'src' => $OUTPUT->pix_url('t/edit'), 'class'=>'icon edit',
                            'alt'=>$streditsummary));
                    $summary .= html_writer::end_tag('a');
                    $summary .= html_writer::empty_tag('br', array());
                    $summary .= html_writer::start_tag('a', array(
                            'href' => $CFG->wwwroot . '/mod/subpage/view.php?id=' .
                                $subpage->get_course_module()->id . '&delete=' .
                                $section->id . '&sesskey=' . sesskey(),
                            'title'=> $strdelete));
                    if (empty($section->sequence)) {
                        $summary .= html_writer::empty_tag('img', array(
                                'src' => $OUTPUT->pix_url('t/delete'), 'class'=>'icon delete',
                                'alt'=>$strdelete));
                    }
                    $summary .= html_writer::end_tag('a');
                    $summary .= html_writer::empty_tag('br', array());
                    $summary .= html_writer::empty_tag('br', array());
                }
                if ($summary !== '') {
                    $content .= html_writer::tag('div', $summary, array('class' => 'summary'));
                }

                $content .= $this->render_section($subpage, $modinfo, $section,
                        $editing, $moveitem, $mods, $modnamesused);
                if ($editing) {
                    $courserenderer = $PAGE->get_renderer('core', 'course');
                    $content .= $courserenderer->course_section_add_cm_control($subpage->get_course(),
                            $section->section);
                    if (!empty($CFG->enablecourseajax) and $PAGE->theme->enablecourseajax) {
                        // hacky way to add list to empty section to allow drag/drop into
                        // empty sections
                        $content = str_replace('</div><div class="section_add_menus">',
                                '</div><ul class="section img-text"><li></li></ul>' .
                                '<div class="section_add_menus">' , $content);
                    }
                }
                //now add returnto links to editing links:
                $pattern = '/mod.php\?[A-Za-z0-9-&;=%:\/\-.]+/';
                $content = preg_replace_callback($pattern,
                        'mod_subpage_renderer::subpage_url_regex', $content);
                if ($section->stealth) {
                    $content .= html_writer::end_tag('div');
                }
            }
            $content .= html_writer::end_tag('div'); //end of div class=content
            $content .= html_writer::end_tag('li');
        }

        $content .= html_writer::end_tag('ul');
        if ($editing) {
            $content .= $this->render_add_button($subpage);
            $content .= $this->render_bulkmove_buttons($subpage);
        }
        return $content;
    }

    /**
     * helper function to add returnurl to strings.
     * @param  $matches
     * @return string
     */
    protected function subpage_url_regex($matches) {
        global $CFG;
        if (strpos($matches[0], 'returnurl')=== false) {
            return $matches[0] . "&amp;returnurl=" . $CFG->wwwroot . '/mod/subpage/view.php?id=' .
                    $this->subpagecm;
        } else {
            return $matches[0];
        }
    }

    /**
     * Display intro section.
     * @param mod_subpage $subpage Module object
     * @return string Intro HTML or '' if none
     */
    public function render_intro(mod_subpage $subpage) {
        // Don't output anything if no text, so we don't get styling around
        // something blank
        if (!$subpage->has_intro()) {
            return '';
        }

        // Make fake activity object in required format, and use to format
        // intro for module with standard function (which handles images etc.)
        $activity = (object)array('intro' => $subpage->get_intro(),
                    'introformat' => $subpage->get_intro_format());
        $intro = format_module_intro(
                    'subpage', $activity, $subpage->get_course_module()->id);

        // Box styling appears to be consistent with some other modules
        $intro = html_writer::tag('div', $intro, array('class' => 'generalbox box',
                    'id' => 'intro'));

        return $intro;
    }

    /**
     * Displays information about the item currently being moved, and a cancel link
     * @param int $cmid coursemodule id
     * @return string
     */
    public function render_cancel_link($cmid) {
        global $USER;
        $sesskey = sesskey();
        $stractivityclipboard =
                strip_tags(get_string('activityclipboard', '', $USER->activitycopyname));
        $cancelurl = new moodle_url('view.php', array('cancelcopy' => true, 'id' => $cmid,
                'sesskey' => $sesskey));

        $cancellink = html_writer::link($cancelurl, get_string('cancel'));
        $content = $stractivityclipboard . '&nbsp;&nbsp;(' . $cancellink . ')';
        return html_writer::tag('div', $content, array('class' => 'clipboard'));
    }

    /**
     * Render single section
     *
     * @param
     * @return
     */
    protected function render_section($subpage, $modinfo, $section, $editing,
            $moveitem, $mods, $modnamesused) {
        global $CFG, $PAGE;
        $courserenderer = $PAGE->get_renderer('core', 'course');
        $content = $courserenderer->course_section_cm_list($subpage->get_course(), $section);
        $content = str_replace($CFG->wwwroot.'/course/mod.php?copy',
                'view.php?id='.$subpage->get_course_module()->id.'&amp;copy', $content);
        $content = str_replace("'togglecompletion.php'", "'" . $CFG->wwwroot .
                "/course/togglecompletion.php'", $content);
        $findcontent = "<input type='hidden' name='completionstate'";
        $content = str_replace($findcontent, "<input type='hidden' name='backto' value='" .
                $CFG->wwwroot.'/mod/subpage/view.php?id=' . $subpage->get_course_module()->id .
                "'/>" . $findcontent, $content);

        return $content;
    }

    /**
     * Render add button used to add a new section.
     *
     * @param
     * @return
     */
    protected function render_add_button($subpage) {
        $addsection = new single_button(new moodle_url('view.php', array(
                'id'=>$subpage->get_course_module()->id,
                'addsection'=>1,
                'sesskey'=>sesskey())),
                get_string('addsection', 'mod_subpage'), 'get');
        return $this->output->render($addsection);
    }

    /**
     * Render buttons used for bulk move.
     *
     * @param
     * @return
     */
    protected function render_bulkmove_buttons($subpage) {
        $moveto = new single_button(new moodle_url('move.php', array(
                'id'=>$subpage->get_course_module()->id,
                'move'=>'to',
                'sesskey'=>sesskey())),
                get_string('movetopage', 'mod_subpage'), 'get');
        $buttons = $this->output->render($moveto);

        // can only move from if there are sections with modules
        $sections = $subpage->get_sections();
        if (!empty($sections)) {
            $sequence = '';
            foreach ($sections as $section) {
                if ($section->sequence !== '') {
                    $sequence .= ','.$section->sequence;
                }
            }
            if ($sequence !== '') {
                $movefrom = new single_button(new moodle_url('move.php', array(
                        'id'=>$subpage->get_course_module()->id,
                        'move'=>'from',
                        'sesskey'=>sesskey())),
                        get_string('movefrompage', 'mod_subpage'), 'get');
                $buttons .= $this->output->render($movefrom);
            }
        }

        return $buttons;
    }
}
