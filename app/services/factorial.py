def factorial(n):
	if n < 0:
		raise ValueError("n must be greater than 0")
	elif n == 0:
		return 1
	else:
		return n * factorial(n-1)