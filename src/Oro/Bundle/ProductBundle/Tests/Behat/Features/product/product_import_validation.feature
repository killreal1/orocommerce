@ticket-BB-16378
@ticket-BAP-18847
Feature: Product import validation
  In order to check file for errors before import
  As an administrator
  I want to be able to get a list of validation errors for imported file

  Scenario: Prepare additional localization
    Given I login as administrator
    And I go to System/Localization/Localizations
    And I click "Create Localization"
    And fill "Localization Form" with:
      | Name       | Ukrainian           |
      | Title      | Ukrainian           |
      | Language   | English             |
      | Formatting | Ukrainian (Ukraine) |
    And I save form
    Then I should see "Localization has been saved" flash message

  Scenario: Validate import file with valid slug prototype
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | names.default.value                                                                                               | attributeFamily.code | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | slugPrototypes.default.value |
      | <b>Test</b><br><img src="http://test.jpg"><strong>Test</strong><a href="http://test.pdf" target="_blank">Test</a> | default_family       | PSKU1 | enabled | simple | in_stock            | set                            | 1                              | invalid,slug^&               |
      | Product 2                                                                                                         | default_family       | PSKU2 | enabled | simple | in_stock            | set                            | 1                              | valid-slug                   |
      | Product 3                                                                                                         | default_family       | PSKU3 | enabled | simple | in_stock            | set                            | 1                              |                              |

  Scenario: Check import error page from the email after validating import file
    Given I validate file
    Then Email should contains the following "Errors: 1 processed: 2, read: 3" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. slugPrototypes[default]: This value should contain only latin letters, numbers and symbols \"-._~\""
    And I login as administrator

  Scenario: Import file with invalid slug prototype
    Given I go to Products/Products
    And I download "Products" Data Template file with processor "oro_product_product_export_template"
    And fill template with data:
      | attributeFamily.code | names.default.value | names.Ukrainian.value | sku   | status  | type   | inventory_status.id | primaryUnitPrecision.unit.code | primaryUnitPrecision.precision | slugPrototypes.default.value |
      | default_family       | Product 1           | Product 1 Ukr         | PSKU1 | enabled | simple | in_stock            | set                            | 1                              | invalid,slug^&               |
      | default_family       | Product 2           | Product 2 Ukr         | PSKU2 | enabled | simple | in_stock            | set                            | 1                              | valid-slug                   |
      | default_family       | Product 3           | Product 3 Ukr         | PSKU3 | enabled | simple | in_stock            | set                            | 1                              |                              |

  Scenario: Check import error page from the email after importing file
    Given I import file
    Then Email should contains the following "Errors: 1 processed: 2, read: 3" text
    When I follow "Error log" link from the email
    Then I should see "Error in row #1. slugPrototypes[default]: This value should contain only latin letters, numbers and symbols \"-._~\""
    And I login as administrator

  Scenario: Check imported products
    Given I go to Products/Products
    Then I should see following grid:
      | SKU   | Name      |
      | PSKU3 | Product 3 |
      | PSKU2 | Product 2 |
    When click edit "PSKU2" in grid
    Then I should see URL Slug field filled with "valid-slug"
    And I go to Products/Products
    When click edit "PSKU3" in grid
    Then I should see URL Slug field filled with "product-3"
