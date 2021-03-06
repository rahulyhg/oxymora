<?php namespace template\oxymora_landingpage;
use KFall\oxymora\pageBuilder\template\iTemplateElement;
use KFall\oxymora\pageBuilder\template\iTemplateElementSettings;

class FullscreenButton implements iTemplateElement, iTemplateElementSettings{

  private $text,$link;

  private $htmlText = '
  <div class="fsContainer">
  <div class="getstartedContainer">
  <button>{text}</button>
  </div>
  </div>

  <script type="text/javascript">
  (function(){
    $(".getstartedContainer button").on("click", function(){
      $(".getstartedContainer").css("background", "rgb(235, 17, 240)");
      setTimeout(function(){window.location.href="{link}";}, 500);
    });
  })();
  </script>
  ';

  public function setSetting($key, $value){
    if(property_exists($this, $key)){
      $this->$key = $value;
    }
  }

  public function getHtml(){
    $html = $this->htmlText;
    $html = str_replace("{text}", $this->text, $html);
    $html = str_replace("{link}", $this->link, $html);
    return $html;
  }

}
