{strip}
  <table class="selector row-highlight">
    <thead class="sticky">
    <tr>
      {foreach from=$columnHeaders item=header}
        <th scope="col">
            {$header}
        </th>
      {/foreach}
    </tr>
    </thead>

    {foreach from=$membershipPeriods item=membershipperiod}
      <tr>
          <td>{$membershipperiod.start_date|crmDate}</td>
          <td>{$membershipperiod.end_date|crmDate}</td>
          <td>{$membershipperiod.renew_timestamp|crmDate}</td>
          <td>
            <center>
              {$membershipperiod.total_contribution_amount|crmMoney:$membershipperiod.contribution_currency}<br>
              <a href="{$membershipperiod.contribution_url}" class="action-item crm-hover-button">View Details</a>
            </center>
          </td>
      </tr>
    {/foreach}
  </table>
{/strip}
