if __name__ == "__main__":
    suite = ProfileManagementTests()
    suite.setup()
    try:
        suite.run_all_tests()
    finally:
        suite.teardown()
