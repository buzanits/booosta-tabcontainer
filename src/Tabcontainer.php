<?php
namespace booosta\tabcontainer;

use \booosta\Framework as b;
b::init_module('tabcontainer');

class tabcontainer extends \booosta\ui\UI
{
  use moduletrait_tabcontainer;

  public $tabsaver = false;
  protected $type = 'standard';
  protected $title;
  
  public function after_instanciation()
  {
    parent::after_instanciation();

    if(is_object($this->topobj) && is_a($this->topobj, "\\booosta\\webapp\\Webapp")):
      $this->topobj->moduleinfo['tabcontainer'] = true;
      if($this->topobj->moduleinfo['jquery']['use'] == '') $this->topobj->moduleinfo['jquery']['use'] = true;
      if($this->topobj->moduleinfo['jquery']['use_ui'] == '') $this->topobj->moduleinfo['jquery']['use_ui'] = true;
    endif;
  }

  public function get_js() 
  { 
    return '';  // todo
    #\booosta\debug($this->tabs);
    if($this->type == 'vertical'):
      $extra = '.addClass("ui-tabs-vertical ui-helper-clearfix")';
      $class = '$("#tabsvert > ul > li").removeClass("ui-corner-top").addClass("ui-corner-left");';
    endif;

    if($this->tabsaver)
      return "$(function() { $('#tabcontainer_$this->id').tabs({ active: $active, 
                activate: function(event, ui) { $.ajax('lib/modules/tabcontainer/savetab.php?id=$this->id&tab=' + ui.newTab.index()); } })$extra; }); $class";

    return "$(function() { $('#tabcontainer_$this->id').tabs({ active: $active })$extra }); $class";
  }

  public function get_htmlonly()
  {
    switch($this->type):
      case 'vertical':
        $headcode = '<div class="row"><div class="col-1"><div class="nav flex-column nav-tabs h-100" id="vert-tabs-tab" role="tablist" aria-orientation="vertical">';
        $titlecode = '';
        $intermediatecode = '</div></div><div class="col-11"> <div class="tab-content" id="vert-tabs-tabContent">';
        $footcode = "</div></div></div>\n";
        $tabcode = '<a class="nav-link {tabclass}" id="{divid}-tab" data-toggle="pill" href="#{divid}" role="tab" aria-controls="vert-tabs-home" aria-selected="{selected}">{label}</a>' . "\n";
        $contentcode = "<div class=\"tab-pane {contentclass}\" id=\"{divid}\" role=\"tabpanel\" aria-labelledby=\"{divid}-tab\">{content}</div>\n";
      break;
      default:
        $headcode = '<div class="card card-primary card-tabs card-outline card-outline-tabs"><div class="card-header p-0 pt-1"><ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">' . "\n";
        $titlecode = "<li class=\"pt-2 px-3\"><h3 class=\"card-title\">$this->title</h3></li>\n";
        $intermediatecode = "</ul></div>\n<div class=\"card-body\"><div class=\"tab-content\" id=\"custom-tabs-two-tabContent\">\n";
        $footcode = "</div></div></div>\n";
        $tabcode = "<li class=\"nav-item\"><a class=\"nav-link {tabclass}\" id=\"{divid-tab}\" data-toggle=\"pill\" href=\"#{divid}\" role=\"tab\" 
                    aria-controls=\"{divid}\" aria-selected=\"{selected}\">{label}</a></li>\n";
        $contentcode = "<div class=\"tab-pane {contentclass}\" id=\"{divid}\" role=\"tabpanel\" aria-labelledby=\"{divid}-tab\">{content}</div>\n";
    endswitch;

    $active = null;
    foreach($this->tabs as $index=>$tab) if($tab['selected']) $active = $index;
    if($active === null) $active = $_SESSION["savedtab_tabcontainer_$this->id"];
    if($active === null) $active = 0;

    $code = '';
    $code .= $headcode;
    if($this->title) $code .= $titlecode;

    // show tabs header with labels
    foreach($this->tabs as $idx=>$tab):
      $label = $tab['label'] ? $this->t($tab['label']) : $this->t($tab['name']);
      if($tab['icon']) $label = $tab['icon'] . " $label";

      $divid = 'tab' . md5($tab['name'] . $this->id);
      $tabclass = $idx == $active ? 'active' : '';
      $selected = $idx == $active ? 'true' : 'false';

      $code .= str_replace(['{label}', '{divid}', '{tabclass}', '{selected}'], [$label, $divid, $tabclass, $selected], $tabcode);
    endforeach;

    $code .= $intermediatecode;

    // show tabs content
    foreach($this->tabs as $idx=>$tab):
      if(is_object($tab['content']) && method_exists($tab['content'], 'get_html')):
        $content = $tab['content']->get_html();
      elseif(is_readable($tab['content']) && is_object($this->topobj)):
        $parser = $this->topobj->get_templateparser();
        $content = $parser->parse_template(file_get_contents($tab['content']), null, $this->topobj->get_TPL());
      elseif(is_object($this->topobj)):
        $parser = $this->topobj->get_templateparser();
        $content = $parser->parse_template($tab['content'], null, $this->topobj->get_TPL());
      else:
        $content = $tab['content'];
      endif;

      $divid = 'tab' . md5($tab['name'] . $this->id);
      $contentclass = $idx == $active ? 'show active' : '';
      $code .= str_replace(['{contentclass}', '{divid}', '{content}'], [$contentclass, $divid, $content], $contentcode);
    endforeach;

    $code .= $footcode;
    return $code;
  }

  public function set_tabs($data) { $this->tabs = $data; }
  public function add_tab($data) { $this->tabs[] = $data; }
  public function set_headline($data) { $this->headline = $data; }
  public function set_tabsaver($data) { $this->tabsaver = $data; }
  public function set_type($data) { $this->type = $data; }
  public function set_title($data) { $this->title = $data; }

  public function set_active_idx($idx)
  {
    foreach($this->tabs as $index=>$tab) $this->tabs[$index]['selected'] = ($index == $idx);
    #\booosta\debug($this->tabs);
  }

  public function set_active($name)
  {
    foreach($this->tabs as $index=>$tab) $this->tabs[$index]['selected'] = ($tab['name'] == $name);
    #\booosta\debug($this->tabs);
  }
}
