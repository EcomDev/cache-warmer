Feature: Cleaning Cache by URL
  As an Socket API User
  I want to clean cache by URL
  So my website receives most recently updated content

  Scenario: Simple url page is cached
    Given I already visited "/item-1" page
    When I visit "/item-1" page
    Then I see that page is cached

  Scenario: Clearing simple page cache
    Given I already visited "/item-1" page
    When I flush cache for "/item-1" page
    Then I see that "/item-1" page cache was cleared

  Scenario: Keeping in tact cache for other pages
    Given I already visited "/item-10" page
    When I flush cache for "/item-1" page
    Then I see that "/item-10" page cache was not cleared

  Scenario: Clearing page cache with variations
    Given I already visited "/item-1" page
      And I already visited "/item-1/" page
    When I flush cache for "/item-1" page with variations
      | /           |
    Then I see that "/item-1" page cache was cleared
     And I see that "/item-1/" page cache was cleared

  Scenario: Keeping in tact cache for pages with different variations
    Given I already visited "/item-1/sub-item" page
    And I already visited "/item-1/" page
    When I flush cache for "/item-1" page with variations
      | /           |
    Then I see that "/item-1/sub-item" page cache was not cleared
