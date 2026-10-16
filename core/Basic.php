<?php

declare(strict_types=1);

namespace pagesAndGroups\core;

use \LEPTON_handle;
use \LEPTON_database;
use \Lepton_singleton;
use \LEPTON_core;
use \LEPTON_tools;
use \lib_twig_box;

class Basic
{
    use LEPTON_singleton;
    
    public array $pageTree = [];
    public array $allGroups =[];
    
    public static $instance;
    
    public function show(): void
    {
        $oTWIG = lib_twig_box::getInstance();
        $oTWIG->registerModule("pagesAndGroups");
        
        echo $oTWIG->render(
            "@pagesAndGroups/list.lte",
            [
                'action'    => "#",
                'leptoken'  => get_leptoken(),
                'allGroups' => $this->allGroups,
                'pageTree'  => $this->pageTree
            ]
        );
    }
    
    public function handleSave(): void
    {
        $save = LEPTON_core::getValue("groups", "array", "post");
        if (!is_null($save))
        {
            // echo LEPTON_tools::display_dev($save, "pre", "ui message green");
            $database = LEPTON_database::getInstance();
            
            foreach ($save as $page => $accessValues)
            {
                $this->cleanGroupsAccess($accessValues);
                
                if (!in_array(1, $accessValues))
                {
                    $accessValues[] = 1;
                }
                
                sort($accessValues);
                $accessString = implode(",", $accessValues);
                
                $database->simple_query(
                    "UPDATE `".TABLE_PREFIX."pages` 
                    SET `admin_groups` = '".$accessString."'
                    WHERE `page_id` =".$page
                );
            }
            
            echo LEPTON_tools::display_dev("Data saved.", "pre", "ui message green");
        } 
    }
    
    protected function cleanGroupsAccess(array &$accessList ): void
    {
        $newList = [];
        foreach ($accessList as $groupId)
        {
            foreach ($this->allGroups as $tempGroup)
            {
                if (($tempGroup['group_id'] == $groupId) && ($tempGroup['backend_access'] == 1)) // !
                {
                    $newList[] = $groupId;
                    break;
                }
            }
        }
        $accessList = $newList;
    }
    
    protected function getPageTree(): void
    {
        LEPTON_handle::register("page_tree");
        
        $fields = ['page_id', 'page_title', 'menu_title', 'parent', 'position', 'visibility', 'link', 'admin_groups'];
        page_tree(0, $this->pageTree, $fields);
    }
    
    protected function getGroups(): void
    {
        LEPTON_database::getInstance()->execute_query(
            "SELECT `group_id`, `name`, `backend_access` FROM `".TABLE_PREFIX."groups`",
            true,
            $this->allGroups,
            true
        );
    }
    
    private function __construct()
    {
        // own stuff here
        $this->getGroups();
        $this->handleSave();
        $this->getPageTree();
    }
}