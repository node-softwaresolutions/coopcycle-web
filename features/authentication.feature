Feature: Authenticate

  Scenario: Login success
    Given the user is loaded:
      | email    | bob@coopcycle.org |
      | username | bob               |
      | password | 123456            |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/login_check" with parameters:
      | key       | value  |
      | _username | bob    |
      | _password | 123456 |
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
      """
      {
        "token": @string@,
        "roles": @array@,
        "username": "bob",
        "email": "bob@coopcycle.org",
        "id": @integer@,
        "refresh_token": @string@,
        "enabled": true
      }
      """

  Scenario: Login by email success
    And the user is loaded:
      | email    | bob@coopcycle.org |
      | username | bob               |
      | password | 123456            |
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/login_check" with parameters:
      | key       | value  |
      | _username | bob@coopcycle.org    |
      | _password | 123456 |
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "token": @string@,
      "roles": @array@,
      "username": "bob",
      "email": "bob@coopcycle.org",
      "id": @integer@,
      "refresh_token": @string@,
      "enabled": true
    }
    """

  Scenario: Login failure
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/login_check" with parameters:
      | key       | value  |
      | _username | nope   |
      | _password | 123456 |
    Then the response status code should be 401
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "code": 401,
      "message": @string@
    }
    """

  Scenario: Authenticated request
    Given the user is loaded:
      | email    | bob@coopcycle.org |
      | username | bob               |
      | password | 123456            |
    And the user "bob" is authenticated
    When I add "Accept" header equal to "application/ld+json"
    And the user "bob" sends a "GET" request to "/api/me"
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "@context": "/api/contexts/User",
      "@id": "/api/users/1",
      "@type": "User",
      "addresses": [],
      "username": "bob",
      "email": "bob@coopcycle.org",
      "roles":["ROLE_USER"]
    }
    """

  Scenario: Register success
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/register" with parameters:
      | key         | value             |
      | _email      | bob@coopcycle.org |
      | _username   | bob               |
      | _password   | 123456            |
      | _givenName  | Bob               |
      | _familyName | Doe               |
      | _telephone  | +33612345678      |
    Then the response status code should be 200
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "id":@integer@,
      "roles":[
        "ROLE_USER"
      ],
      "username":"bob",
      "email":"bob@coopcycle.org",
      "enabled": true,
      "token":@string@,
      "refresh_token":@string@
    }
    """

  Scenario: Register failure
    When I add "Accept" header equal to "application/ld+json"
    And I send a "POST" request to "/api/register" with parameters:
      | key         | value             |
      | _email      | bob@coopcycle.org |
    Then the response status code should be 400
    And the response should be in JSON
    And the JSON should match:
    """
    {
      "type":"https://tools.ietf.org/html/rfc2616#section-10",
      "title":@string@,
      "detail":@string@,
      "violations":[
        {
          "propertyPath":"data.username",
          "message":@string@
        },
        {
          "propertyPath":"data.givenName",
          "message":@string@
        },
        {
          "propertyPath":"data.familyName",
          "message":@string@
        },
        {
          "propertyPath":"data.telephone",
          "message":@string@
        }
      ]
    }
    """
