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
    #return '$("#vert-tabs-tab").on("click", function(e) { console.log(e); })';
    #\booosta\debug($this->tabs);
    $js = '';

    if($this->tabsaver)
      foreach($this->tabs as $idx=>$tab):
        $divid = 'tab' . md5($tab['name'] . $this->id);
        $js .= "$('#$divid-tab').on('click', function(e) { var tabid = e.currentTarget.attributes.id.nodeValue; $.ajax('vendor/booosta/tabcontainer/src/savetab.php?id=$this->id&tab=' + tabid); }); ";
      endforeach;

    return $js;
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
        $tabcode = "<li class=\"nav-item\"><a class=\"nav-link {tabclass}\" id=\"{divid}-tab\" data-toggle=\"pill\" href=\"#{divid}\" role=\"tab\" 
                    aria-controls=\"{divid}\" aria-selected=\"{selected}\">{label}</a></li>\n";
        $contentcode = "<div class=\"tab-pane {contentclass}\" id=\"{divid}\" role=\"tabpanel\" aria-labelledby=\"{divid}-tab\">{content}</div>\n";
    endswitch;

    $active = null;
    foreach($this->tabs as $index=>$tab) if($tab['selected']) $active = 'tab' . md5($tab['name'] . $this->id);
    if($active === null) $active = $_SESSION["savedtab_tabcontainer_$this->id"];
    if($active === null) $active = 0;
    #b::debug("id: $this->id, active: $active");

    $code = '';
    $code .= $headcode;
    if($this->title) $code .= $titlecode;

    // show tabs header with labels
    foreach($this->tabs as $idx=>$tab):
      $label = $tab['label'] ? $this->t($tab['label']) : $this->t($tab['name']);

      if($tab['icon']):
        if(is_readable('tpl/tab_icon.tpl')) $tpl = file_get_contents('tpl/tab_icon.tpl');
        elseif(is_readable('vendor/booosta/tabcontainer/src/tab_icon.tpl')) $tpl = file_get_contents('vendor/booosta/tabcontainer/src/tab_icon.tpl');
        else $tpl = '{icon}';

        $icon = str_replace('{icon}', $tab['icon'], $tpl);
        $label = "$icon $label";
      endif;

      $divid = 'tab' . md5($tab['name'] . $this->id);
      if($active === 0) $active = "$divid-tab";   // if not set, select first item
      $tabclass = "$divid-tab" == $active ? 'active' : '';
      $selected = "$divid-tab" == $active ? 'true' : 'false';
      #b::debug("divid: $divid, active: $active");

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
      $contentclass = "$divid-tab" == $active ? 'show active' : '';
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
