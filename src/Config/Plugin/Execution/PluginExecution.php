<?php

namespace Siarko\Plugins\Config\Plugin\Execution;

enum PluginExecution
{
    case BEFORE;
    case AROUND;
    case AFTER;

}