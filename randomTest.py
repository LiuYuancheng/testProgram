def bsd_rand(seed):
	def rand():
		rand.seed = (1103515245*rand.seed + 12345) & 0x7fffffff
		return rand.seed
	rand.seed = seed
	return rand

a = bsd_rand(0)
for i in range(5):
	print(a()%128000+1)
