<?php
namespace booosta\tabcontainer;

require_once __DIR__ . '/../../../../vendor/autoload.php';

use booosta\Framework as b;
b::croot();
b::load();

class SavetabApp extends \booosta\webapp\Webapp
{
  protected function action_default()
  {
    $id = $this->VAR['id'];
    $tab = $this->VAR['tab'];
    #b::debug("Saving Tab '$tab' for id '$id'");

    $_SESSION["savedtab_tabcontainer_$id"] = $tab;
    \booosta\ajax\Ajax::print_response(null, []);
    $this->no_output = true;
  }
}

#b::debug('start');
$app = new SavetabApp();
$app();
