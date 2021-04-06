# Contribution guidelines for Conduction

If you want to contribute to Conduction, we ask you to follow these guidelines.

## Reporting bugs
If you encounter a bug in this component, please check if an issue already exists in the issue section of this repository.

If such an issue does not exist you can create one [here](/../../issues/new?assignees=&labels=&template=bug_report.md&title=).

Make sure you answer each step in detail.

## Requesting new features
Before requesting a new feature please check if the feature isn't listed in either [roadmap](ROADMAP.md) or the issue tab in Github.

If this is not the case you can create one [here](/../../issues/new?assignees=&labels=&template=feature_request.md&title=).

Make sure you answer each step in detail.

## Forking the repository
To start you must first fork the repository, which can be done [here](/../../fork).
Once this is done you can clone it to your local machine.

## Making the changes
On your local machine, create a new branch on the development branch.
please use the following naming convention for your branch name:
- `issue/issue-number`
- `feature/feature-name`

Once you have made changes or additions to the code, you can commit them (try to keep the commit message descriptive but short). 
Be sure to format your commit message to include the issue number.

## Code quality & conventions
To keep the code clean and readable, Conduction uses the following coding standards:
- [Style CI](https://styleci.io/)
- [Better code hub](https://bettercodehub.com/repositories)
- [PSR-02](https://docs.opnsense.org/development/guidelines/psr2.html)
- [Public Code](https://publiccode.net/)

### DocBlocks
We use [DocBlock](https://en.wikipedia.org/wiki/Docblock) annotations in our projects.
Docblock annotations are a tool to embed metadata inside the documentation section which can then be processed by some tool.

A few examples of how we use this in our projects:

```php
    /**
     * @var string Name of this application
     *
     * @example application name
     *
     * @Groups({"read","write"})
     * @Assert\NotNull
     * @ORM\Column(type="string", length=255, nullable=false)
     */
    private $name;


    /**
     * Function description comes here.
     *
     * @param string $code the code received by id-vault oauth endpoint.
     * @param string $applicationId id of your id-vault application.
     * @param string $secret secret of your id-vault application.
     * @param string $state (optional) A random string used by your application to identify a unique session
     *
     * @return array|false information about what this function returns
     */
    public function authenticateUser(string $code, string $applicationId, string $secret, string $state = '')
    {

    }
```

You can read more about this here:
- [Doctrine](https://www.doctrine-project.org/projects/doctrine-orm/en/2.7/reference/annotations-reference.html) 
- [DocBlock](https://en.wikipedia.org/wiki/Docblock)
- [api-platform](https://api-platform.com/docs/core/getting-started/)

### Postman
To ensure your changes work we can test this using [postman](https://www.postman.com/).

Make sure to provide a working postman test script in your pull request.

### Unit tests
- TODO

## Prepare your Pull Request

When preparing your Pull Request you have to keep the following things in mind:
- try not to break backwards compatibility, PRs that break backwards compatibility have less chance to be merged;
- make sure you follow the coding standards defined in the code quality section
- write clear and descriptive commit messages

If your pull request is a feature request make sure to add it to the [changelog](CHANGELOG.md).

In the pull request description, give as much detail as possible about your changes (don’t hesitate to give code examples to illustrate your points). If your pull request is about adding a new feature or modifying an existing one, explain the rationale for the changes. The pull request description helps the code review and it serves as a reference when the code is merged (the pull request description and all its associated comments are part of the merge commit message).
Make sure your pull requests also refers to at least one or more of the issues you worked on.


Whenever you feel that your code is ready for submission you can make a pull request to the development branch.


Keep in mind that when you create a PR you transfer the ownership of your code.

While the prerequisites above must be satisfied prior to having your pull request reviewed, the reviewer(s) may ask you to complete additional design work, tests, or other changes before your pull request can be ultimately accepted.
