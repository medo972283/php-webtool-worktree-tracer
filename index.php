
<?php
    /**
     * Get a list of projects which are not generate from the worktree
     *
     * @param  string  $rootDir
     * @return array
     */
    function getNativeProjectList($rootDir) {

        // Define the name list for scandir() to filter out
        $excludeList = ['.', '..', '.vscode-server'];

        // Exclude specified file name
        $scanList = array_diff(scandir($rootDir), $excludeList);

        $result = [];

        foreach ($scanList as $file) {

            // Get absolute path of file
            $filePath = join(DIRECTORY_SEPARATOR, [$rootDir, $file]);

            // Determine whether the file is a directory
            if (is_dir($filePath)) {

                // Get absolute path of '.git'
                $gitPath = join(DIRECTORY_SEPARATOR, [$filePath, '.git']);

                // Only collect the file which contains '.git' of directory type, cuz the '.git' created by worktree is just a file to show the gitdir link
                is_dir($gitPath) && $result[] = $file;
            }
        }

        return $result;
    }

    /**
     * Get the branch info of worktrees under project
     *
     * @param  string  $rootDir
     * @param  array   $projectList
     * @return array   [project => [worktree => branch]]
     */
    function getWorktreeInfo($rootDir, $projectList) {

        $result = [];

        foreach ($projectList as $dirName) {

            // Get absolute path of target project's worktrees directory
            $worktreesPath = join(DIRECTORY_SEPARATOR, [$rootDir, $dirName, '.git', 'worktrees']);

            // Exclude specified file name
            $scanList = array_diff(scandir($worktreesPath), ['.', '..']);

            foreach ($scanList as $file) {

                // Get absolute path of Git HEAD file
                $gitHeadPath = join(DIRECTORY_SEPARATOR, [$worktreesPath, $file, 'HEAD']);

                // Filled in branch name
                $result[$dirName][] = [
                  'worktree' => $file,
                  'branch' => end(explode(DIRECTORY_SEPARATOR, file_get_contents($gitHeadPath)))
                ];
            }
        }

        return $result;
    }

    // Root directory
    $rootDir = dirname(dirname(__FILE__));

    // Get the project list under the root
    $nativeProjectList = getNativeProjectList($rootDir);

    // Get the worktree info of projects
    $worktreeInfo = getWorktreeInfo($rootDir, $nativeProjectList);
?>

<!-- Get PHP value -->
<script type="text/javascript">
  const WORKTREE_INFO = Object.freeze(<?= json_encode($worktreeInfo); ?>);
</script>

<style type="text/css">

  body::-webkit-scrollbar {
    display: none;
  }

  .design-layout {
    margin-top: 5vh !important;
    margin-bottom: 5vh !important;
  }
</style>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Bootstrap css 5.0.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <!-- Bootstrap js 5.0.0 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>
    <!-- jQuery 3.6.0 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>
  </head>
  <body>
    <div class="container">
      <div class="row">
        <div class="accordion-block design-layout"></div>
      </div>
    </div>
  </body>
</html>

<!-- Template - accordion item -->
<script type="text/template" id="tmpl-accordion-item">
  <div class="accordion-item">

    <!-- Header -->
    <h2 class="accordion-header">
      <button class="accordion-button" type="button" data-bs-toggle="collapse"></button>
    </h2>

    <!-- Content -->
    <div class="accordion-collapse collapse show">
      <div class="accordion-body"></div>
    </div>
  </div>
</script>

<!-- Template - table -->
<script type="text/template" id="tmpl-table">
  <table class="table table-striped table-hover">
    <thead>
      <tr>
        <th scope="col"></th>
        <th scope="col">Worktree</th>
        <th scope="col">Branch</th>
      </tr>
    </thead>
    <tbody></tbody>
  </table>
</script>

<script type="text/javascript">

  (function (root, app) {
    $(function () {
      app();
    });
  })(self || this, function () {
    'use strict';

    // Main element
    var $mainEl = $('.container');

    // Constructor
    var constructor = function () {
      accordionBlock.init();
    };

    // Table block
    var tableBlock = {
      $el: $($('#tmpl-table').prop('innerHTML')),
      build: function (data) {
        let $table = this.$el.clone();
        let $tbody = $table.find('tbody');

        // Build table row
        $.each(data, function (idx, row) {
          $('<tr>')
            .append($('<th>').prop({scope: 'row'}).text(idx))
            .append($('<td>').text(row.worktree))
            .append($('<td>').text(row.branch))
            .appendTo($tbody);
        });

        return $table;
      },
    };

    // Accordion block
    var accordionBlock = {
      el: {
        $block: $mainEl.find('.accordion-block'),
        $accordionItem: $($('#tmpl-accordion-item').prop('innerHTML')),
      },
      data: WORKTREE_INFO,
      init: function () {

        // The HTML block for accordion
        let $block = accordionBlock.el.$block;

        // Outer div element of accordion defined by bootstrap
        let $accordionWrapper = $('<div>').addClass('accordion');

        $.each(this.data, function (project, rows) {
          let $accordionItem = accordionBlock.el.$accordionItem.clone();
          let $table = tableBlock.build(rows);

          // accordion-button
          $accordionItem
            .find('.accordion-button')
            .attr('data-bs-target', `#collapse-${project}`)
            .text(project);

          // accordion-collapse
          $accordionItem
            .find('.accordion-collapse')
            .prop({
              id: `collapse-${project}`,
            });

          // Append table element to accordion item
          $accordionItem
            .find('.accordion-body')
            .append($table);

          $accordionWrapper.append($accordionItem);
        });

        $accordionWrapper.appendTo($block);
      },
    };

    constructor();
  });

</script>