# Simple file write test for workspace permissions
with open('tests/bdd/results/file_write_test.txt', 'w') as f:
    f.write('Workspace file write test successful.')
print('File write test completed.')
