<?php
/*
 * Copyright 2014 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

namespace Google\Service\SecretManager;

class Rotation extends \Google\Model
{
  public $nextRotationTime;
  public $rotationPeriod;

  public function setNextRotationTime($nextRotationTime)
  {
    $this->nextRotationTime = $nextRotationTime;
  }
  public function getNextRotationTime()
  {
    return $this->nextRotationTime;
  }
  public function setRotationPeriod($rotationPeriod)
  {
    $this->rotationPeriod = $rotationPeriod;
  }
  public function getRotationPeriod()
  {
    return $this->rotationPeriod;
  }
}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Rotation::class, 'Google_Service_SecretManager_Rotation');
