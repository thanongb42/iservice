-- Fix: learning_resources.id was missing PRIMARY KEY / AUTO_INCREMENT
-- Without this, every INSERT (via admin/api/learning_resources_api.php "add" action)
-- writes id = 0, corrupting data on first use of the Add CRUD function.
-- Run this BEFORE using the new modal-based CRUD on admin/learning_resources.php.

ALTER TABLE `learning_resources`
  MODIFY `id` INT(11) NOT NULL AUTO_INCREMENT,
  ADD PRIMARY KEY (`id`);

-- Ensure AUTO_INCREMENT continues after the highest existing id
ALTER TABLE `learning_resources` AUTO_INCREMENT = 11;
