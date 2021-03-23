
<?php
    /**
     * Get a list of projects exclude the worktrees directory
     *
     * @param  string  $rootDir
     * @return array
     */
    function getNativeProjectList($rootDir) {

        // Define the file name list for scandir() to filter out
        $excludeList = ['.', '..', '.vscode-server'];

        // Get the project list under the root directory
        $projectList = array_diff(scandir($rootDir), $excludeList);

        $result = [];

        foreach ($projectList as $fileName) {

            // Get absolute path of file
            $filePath = join(DIRECTORY_SEPARATOR, [$rootDir, $fileName]);

            // Determine whether the file is a directory
            if (is_dir($filePath)) {

                // .git file path
                $gitPath = join(DIRECTORY_SEPARATOR, [$filePath, '.git']);

                // Only collect the file which contains '.git' of directory type, cuz the '.git' created by worktree is just a plaintext to show the gitdir link
                is_dir($gitPath) && $result[] = $fileName;
            }
        }

        return $result;
    }

    /**
     * Execute bash command of git to get git log info
     *
     * @param  string  $gitPath
     * @return array
     */
    function _getGitDetail($gitPath)
    {
        // Get absolute path of git HEAD file
        $gitHeadPath = join(DIRECTORY_SEPARATOR, [$gitPath, 'HEAD']);

        // Execute git log bash to get last commit raw body (unwrapped subject and body)
        $commitBody = [];
        exec('git --git-dir=' . $gitPath . ' log --pretty="%B" -n1', $commitBody);

        // Execute git log bash to get last commit ID in short
        $commitHashID = trim(exec('git --git-dir=' . $gitPath . ' log --pretty="%h" -n1'));

        // Execute git log bash to get last commit date in ISO 8601-like format
        $committerDate = trim(exec('git --git-dir=' . $gitPath . ' log --pretty="%ci" -n1'));

        // Return the git info
        return [
            'branch' => end(explode(DIRECTORY_SEPARATOR, file_get_contents($gitHeadPath))),
            'commitBody' => implode('<br>', $commitBody),
            'commitID' => $commitHashID,
            'committerDate' => $committerDate,
        ];
    }

    /**
     * Get the git info of worktrees that belongs to the Project
     *
     * @param  string  $rootDir
     * @param  array   $projectList
     * @return array
     */
    function getWorktreeInfo($rootDir, $projectList) {

        $result = [];

        foreach ($projectList as $projectName) {

            /**
             * Process project list
             */

            // .git file path of project
            $projectGitPath = join(DIRECTORY_SEPARATOR, [$rootDir, $projectName, '.git']);

            // Build git info of project
            $result[$projectName][] = [
                'worktree' => $projectName,
            ] + _getGitDetail($projectGitPath);

            /**
             * Process worktree list
             */
            // Get absolute path of target project's "worktrees" directory
            $worktreesPath = join(DIRECTORY_SEPARATOR, [$rootDir, $projectName, '.git', 'worktrees']);

            // Get a name list of worktrees that belongs to the project, and exclude not related ones
            $worktreeList = array_diff(scandir($worktreesPath), ['.', '..']);

            foreach ($worktreeList as $worktreeName) {

                // Get absolute path of worktree's .git file
                $worktreeGitPath = join(DIRECTORY_SEPARATOR, [$rootDir, $worktreeName, '.git']);

                // Get absolute path of worktree's Git HEAD file
                $worktreeHeadPath = join(DIRECTORY_SEPARATOR, [$worktreesPath, $worktreeName, 'HEAD']);

                // Build git info of worktree
                $result[$projectName][] = [
                    'worktree' => $worktreeName,
                    'branch' => end(explode(DIRECTORY_SEPARATOR, file_get_contents($worktreeHeadPath))),
                ] + _getGitDetail($worktreeGitPath);
            }
        }

        return $result;
    }

    // Root directory
    $rootDir = dirname(dirname(__FILE__));

    // Get the project list at the root dir
    $nativeProjectList = getNativeProjectList($rootDir);

    // Get the worktrees's Git detail info of projects
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

  .master-row {
    color: #c44569 !important;
    font-weight: bolder;
  }

  .accordion-button {
    font-size: 1.5rem !important;
    font-weight: bolder;
  }
</style>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <!-- Bootstrap css v5.0.0 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-BmbxuPwQa2lc/FVzBcNJ7UAyJxM6wuqIj61tLrc4wSX0szH/Ev+nYRRuWlolflfl" crossorigin="anonymous">
    <!-- jQuery v3.5.1 -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js" integrity="sha384-DfXdz2htPH0lsSSs5nCTpuj/zy4C+OGpamoFVy38MVBnE+IbbVYUew+OrCXaRkfj" crossorigin="anonymous"></script>
    <!-- Bootstrap bundle js v5.0.0 -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0-beta2/dist/js/bootstrap.bundle.min.js" integrity="sha384-b5kHyXgcpbZJO/tY9Ul7kGkf1S0CWuKcCD38l8YkeH8z8QjE0GmW1gYU5S9FOnJ0" crossorigin="anonymous"></script>
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
        <th scope="col">Last Commit</th>
        <th scope="col">Commit ID</th>
        <th scope="col">Committer Date</th>
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

    // Popover Block
    var popoverBlock = {
      option: {
        html: true,
        container: 'body',
        content: '',
        placement: 'bottom',
        customClass: 'mw-100',
      },
      build: function ($el, data) {

        let worktreeDel = $('<span>')
          .addClass('m-1')
          .text(`git worktree remove --force ${data.worktree}`)
          .prop('outerHTML');

        let branchDel = $('<span>')
          .addClass('m-1')
          .text(`git branch --delete --force ${data.branch}`)
          .prop('outerHTML');

        let originBranchDel = $('<span>')
          .addClass('m-1')
          .text(`git push origin -d ${data.branch}`)
          .prop('outerHTML');

        let option = $.extend(true, {}, this.option, {
          content: [worktreeDel, branchDel, originBranchDel].join('<br>'),
        });

        // new bootstrap.Popover
        $el.popover(option);
      },
    }

    // Table block
    var tableBlock = {
      $el: $($('#tmpl-table').prop('innerHTML')),
      build: function (data) {
        let $table = this.$el.clone();
        let $tbody = $table.find('tbody');
        let deleteIcon = '<svg id="Layer_1_1_" enable-background="new 0 0 64 64" height="24" viewBox="0 0 64 64" width="24" xmlns="http://www.w3.org/2000/svg"><path d="m14.296 25.248 4.475 34.013c.131.995.979 1.739 1.983 1.739h22.492c1.004 0 1.852-.744 1.983-1.739l3.455-26.261 1.316-10h-33.456z" fill="#57a5ff"/><path d="m30 31v22c0 1.105.895 2 2 2s2-.895 2-2v-22c0-1.105-.895-2-2-2s-2 .895-2 2z" fill="#f2f8ff"/><path d="m22 31v22c0 1.105.895 2 2 2s2-.895 2-2v-22c0-1.105-.895-2-2-2s-2 .895-2 2z" fill="#f2f8ff"/><path d="m38 31v22c0 1.105.895 2 2 2s2-.895 2-2v-22c0-1.105-.895-2-2-2s-2 .895-2 2z" fill="#f2f8ff"/><path d="m30 17 4 2h5l-2-5-4-1z" fill="#004fa8"/><path d="m48 10-5 1v2l1 4 8-1-3-4z" fill="#004fa8"/><path d="m30.172 3.716c-.781-.781-2.047-.781-2.828 0l-22.628 22.627c-.781.781-.781 2.047 0 2.828l2.828 2.829 25.456-25.456z" fill="#57a5ff"/><path d="m45.229 59.261.391-2.973c-12.834-4.507-21.688-17.063-20.897-31.305l.11-1.983h-8.289l-2.248 2.248 4.475 34.013c.131.995.979 1.739 1.983 1.739h22.492c1.004 0 1.852-.744 1.983-1.739z" fill="#303030" opacity=".12"/><path d="m27.029 8.971-21.257 21.257 1.772 1.772 25.456-25.456-2.828-2.828c-.781-.781-2.047-.781-2.828 0l-.314.314c1.364 1.364 1.364 3.576-.001 4.941z" fill="#303030" opacity=".12"/><path d="m49 12-1-2-1.818.364.818 1.636 3 4-6.061.758.061.242 8-1z" fill="#303030" opacity=".12"/><path d="m35 15 1.6 4h2.4l-2-5-4-1-.947 1.263z" fill="#303030" opacity=".12"/><path d="m10.373 20.686-2.121-2.121c-1.17-1.169-1.17-3.073 0-4.243l7.071-7.071c1.169-1.17 3.072-1.171 4.243 0l2.121 2.121-1.414 1.414-2.121-2.121c-.39-.39-1.025-.389-1.415 0l-7.071 7.071c-.39.39-.39 1.024 0 1.415l2.121 2.121z" fill="#57a5ff"/><g fill="#004fa8"><path d="m44 2h2v2h-2z"/><path d="m54.022 9.793-1.219-1.586c2.667-2.05 6.089-2.848 9.393-2.188l-.392 1.961c-2.734-.545-5.571.115-7.782 1.813z"/><path d="m57 12h2v2h-2z"/><path d="m50 7h-2c0-2.757 2.243-5 5-5v2c-1.654 0-3 1.346-3 3z"/></g></svg>';

        // Build table row
        $.each(data, function (idx, row) {
          let $deleteBtn = $('<button>')
            .addClass('btn ctrl-delete-btn')
            .html(deleteIcon);

          // Build popover
          popoverBlock.build($deleteBtn, row);

          let $tr = $('<tr>')
            .append($('<th>').addClass('align-middle').prop({scope: 'row'}).append($deleteBtn))
            .append($('<td>').addClass('align-middle').text(row.worktree))
            .append($('<td>').addClass('align-middle').text(row.branch))
            .append($('<td>').addClass('align-middle').html(row.commitBody))
            .append($('<td>').addClass('align-middle').text(row.commitID))
            .append($('<td>').addClass('align-middle').text(row.committerDate))

          idx === 0 && $tr.addClass('master-row');

          $tr.appendTo($tbody);
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