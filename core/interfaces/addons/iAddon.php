<?php namespace KFall\oxymora\addons;

interface iAddon{
  // ========================================
  //  CONSTRUCT
  // ========================================
  public function __construct($permissionManager);

  // ========================================
  //  EVENTS
  // ========================================

  // Start/Stop Events
  public function onInstallation();
  public function onEnable();
  public function onDisable();

  // Dashboard
  public function onOpen();
  public function onTabChange($tab);

  // Page
  public function onPageOpen($page);

}
