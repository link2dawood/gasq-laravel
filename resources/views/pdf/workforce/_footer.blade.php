{{--
    Footer pinned to the bottom of every page.

    Vars: $page (int), $pages (int), $reportNumber, $reportType, $reportDate,
          $orgName.
--}}
<div class="footer">
  <table class="footbar" cellpadding="0" cellspacing="0">
    <tr>
      <td style="width:480px; vertical-align:middle;">
        <p class="org">{{ $orgName }}</p>
        <p class="meta">{{ $reportNumber }} &nbsp;·&nbsp; {{ $reportType }} &nbsp;·&nbsp; Confidential — for authorized recipients only</p>
      </td>
      <td style="width:258px; vertical-align:middle; text-align:right;">
        <p class="org">Page {{ $page }} of {{ $pages }}</p>
        <p class="meta">{{ $reportDate }}</p>
      </td>
    </tr>
  </table>
</div>
