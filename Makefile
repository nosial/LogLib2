all: target/release/net.nosial.loglib2.ncc target/debug/net.nosial.loglib2.ncc
target/release/net.nosial.loglib2.ncc:
	ncc build --configuration release --log-level debug
target/debug/net.nosial.loglib2.ncc:
	ncc build --configuration debug --log-level debug


docs:
	phpdoc --config phpdoc.dist.xml

clean:
	rm target/release/net.nosial.loglib2.ncc
	rm target/debug/net.nosial.loglib2.ncc
	rm target/docs
	rm target/cache

.PHONY: all install clean docs