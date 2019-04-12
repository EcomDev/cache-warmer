Feature: Cleaning Cache by Header
  As an Socket API User
  I want to clean cache by page headers
  So I can achieve better cache segmentation

  Scenario: Single header response
    Given I already visited "/item-1" page with response headers
      | X-Cache-Tag | Tag1 |
    When I flush cache for pages with header "X-Cache-Tag" equal to "Tag1"
    Then I see that "/item-1" page cache was cleared

  Scenario: Multiple header values response
    Given I already visited "/item-2" page with response headers
      | X-Cache-Tag | Tag1 |
      | X-Cache-Tag | Tag2 |
      | X-Cache-Tag | Tag3 |
    When I flush cache for pages with header "X-Cache-Tag" equal to "Tag2"
    Then I see that "/item-2" page cache was cleared

  Scenario: Not matched pages are not affected
    Given I already visited "/item-2" page with response headers
      | X-Cache-Tag | Tag1 |
    When I flush cache for pages with header "X-Cache-Tag" equal to "Tag2"
    Then I see that "/item-2" page cache was not cleared

  Scenario: Multiple page caches are cleared by single flush
    Given I already visited "/item-2" page with response headers
        | X-Cache-Tag | Tag1 |
      And I already visited "/item-1" page with response headers
        | X-Cache-Tag | Tag1 |
      And I already visited "/item-3" page with response headers
        | X-Cache-Tag | Tag2 |
    When I flush cache for pages with header "X-Cache-Tag" equal to "Tag1"

    Then I see that "/item-1" page cache was cleared
     And I see that "/item-2" page cache was cleared
     And I see that "/item-3" page cache was not cleared
