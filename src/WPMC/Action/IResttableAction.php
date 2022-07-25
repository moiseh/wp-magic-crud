<?php
namespace WPMC\Action;

interface IResttableAction
{
    public function getRestMethod();
    public function getExposeAsRest();
}