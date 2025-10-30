# Project Rules & Constitution

These rules must be followed at all times during the development of the WhatsApp Customer Care System.

## Core Development Rules

1. **Always track your job in the progress.md file to know what we did**
   - Document every completed task, current work, and blockers
   - Keep progress.md up-to-date after each significant change

2. **Always make sure to run tests, everytime you code run tests on the laravel either pest or unit testing and also use the browser mcp to know more info**
   - Run relevant tests after each code change
   - Use `php artisan test --filter=testName` for targeted testing
   - Leverage browser MCP to verify UI changes and functionality
   - Never skip testing - it's critical for quality assurance

3. **You will always need to be clean, do not create mess not too much files if you need to test something remove after you use that**
   - Clean up temporary test files, scripts, or helper files
   - Keep the codebase organized and maintainable
   - Remove debugging code before finalizing

4. **Always know your goals and use the initial plan**
   - Reference `plan.md` for business logic and requirements
   - Stay aligned with the original specifications
   - Follow the phased approach outlined in the implementation plan

5. **Create modules.md file to know all the features the app has and add subtasks and track what we did and what is upcoming and what is failed and what is postponed and what is not needed**
   - Maintain comprehensive feature tracking
   - Update module statuses: upcoming, in-progress, completed, failed, postponed, not-needed
   - Document subtasks for each feature

## Additional Guidelines

- Follow Laravel 12 conventions and best practices
- Use Filament 3.0 patterns for admin panel development
- Implement proper error handling and logging
- Write descriptive commit messages
- Keep code DRY (Don't Repeat Yourself)
- Use type hints and return types for all methods
- Follow the 24-hour service window logic strictly for WhatsApp conversations



