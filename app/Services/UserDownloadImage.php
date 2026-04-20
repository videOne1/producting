<?php
class UserDownloadImage
{
    public function getDownloadUrl($user)
    {
        return $user->image;
    }
}